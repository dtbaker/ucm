import mysql from 'mysql2/promise';

/**
 * A read-only window onto the database the app under test is using.
 *
 * The browser suites cannot see what a save actually wrote, so a test that wants to
 * prove it left nothing behind has to count rows. Everything here is SELECT only: the
 * suites create and delete through the app's own forms, never through this.
 *
 * The connection details default to what docker-compose.yml publishes. A worktree
 * running its own container against its own database overrides UCM_DB_NAME, and CI
 * reaches the compose database on the same published port.
 */

const CONFIG = {
  host: process.env.UCM_DB_HOST || '127.0.0.1',
  port: Number( process.env.UCM_DB_PORT || 8096 ),
  user: process.env.UCM_DB_USER || 'ucm',
  password: process.env.UCM_DB_PASS || 'ucm',
  database: process.env.UCM_DB_NAME || 'ucm',
};

/** The table prefix the app is configured with. */
export const PREFIX = process.env.UCM_DB_PREFIX || 'ucm_';

async function withConnection<T>( run: ( c: mysql.Connection ) => Promise<T> ): Promise<T> {
  const connection = await mysql.createConnection( CONFIG );
  try {
    return await run( connection );
  } finally {
    await connection.end();
  }
}

/** Describes where the rows a suite creates can be found. */
export type Fixture = {
  /** table, without the prefix */
  table: string;
  /** the column the fixture name lands in */
  column: string;
  /** SQL LIKE pattern matching the names the suites use */
  like: string;
};

/**
 * Count the rows matching each fixture, in one round trip.
 *
 * Returns a count per fixture, in the order they were given. An identifier that is not
 * a plain name is rejected rather than escaped: these come from a constant in the test
 * file, so anything else is a typo, and quietly interpolating it would be the one place
 * in the suite that builds SQL from a string.
 */
export async function countFixtures( fixtures: Fixture[] ): Promise<number[]> {
  for ( const { table, column } of fixtures ) {
    for ( const identifier of [ table, column ] ) {
      if ( ! /^[a-z_][a-z0-9_]*$/.test( identifier ) ) {
        throw new Error( `not a table or column name: ${ identifier }` );
      }
    }
  }
  if ( ! fixtures.length ) {
    return [];
  }

  const sql = fixtures
    .map( f => `SELECT COUNT(*) c FROM \`${ PREFIX }${ f.table }\` WHERE \`${ f.column }\` LIKE ?` )
    .join( ' UNION ALL ' );

  return withConnection( async connection => {
    const [ rows ] = await connection.query( sql, fixtures.map( f => f.like ) );

    return ( rows as { c: number }[] ).map( r => Number( r.c ) );
  } );
}

/**
 * The ids of the rows matching a fixture, oldest first.
 *
 * Used by the cleanup chore to find what to remove. It reads ids only - the delete
 * itself still goes through the app, so each module takes its dependent rows with it.
 */
export async function idsMatching( fixture: Fixture, idColumn: string, limit = 500 ): Promise<string[]> {
  for ( const identifier of [ fixture.table, fixture.column, idColumn ] ) {
    if ( ! /^[a-z_][a-z0-9_]*$/.test( identifier ) ) {
      throw new Error( `not a table or column name: ${ identifier }` );
    }
  }
  const sql = `SELECT \`${ idColumn }\` id FROM \`${ PREFIX }${ fixture.table }\``
    + ` WHERE \`${ fixture.column }\` LIKE ? ORDER BY \`${ idColumn }\` LIMIT ${ Number( limit ) | 0 }`;

  return withConnection( async connection => {
    const [ rows ] = await connection.query( sql, [ fixture.like ] );

    return ( rows as { id: number | string }[] ).map( r => String( r.id ) );
  } );
}

/** Row counts for whole tables, keyed by the name given (without the prefix). */
export async function countTables( tables: string[] ): Promise<Record<string, number>> {
  for ( const table of tables ) {
    if ( ! /^[a-z_][a-z0-9_]*$/.test( table ) ) {
      throw new Error( `not a table name: ${ table }` );
    }
  }
  if ( ! tables.length ) {
    return {};
  }

  const sql = tables
    .map( t => `SELECT ${ mysqlQuote( t ) } t, COUNT(*) c FROM \`${ PREFIX }${ t }\`` )
    .join( ' UNION ALL ' );

  return withConnection( async connection => {
    const [ rows ] = await connection.query( sql );
    const counts: Record<string, number> = {};
    for ( const row of rows as { t: string; c: number }[] ) {
      counts[ row.t ] = Number( row.c );
    }

    return counts;
  } );
}

/** Only ever called with a name that has already passed the identifier test above. */
function mysqlQuote( value: string ): string {
  return `'${ value }'`;
}

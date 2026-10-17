/**
 * Shared helpers for the suites that read PHP diagnostics out of a response body.
 *
 * includes/config.php turns error reporting off unless debug mode is on, so these only
 * find anything when the app runs with UCM_DEBUG=1 (docker-compose.yml sets it). Each
 * suite that uses them also checks that diagnostics really are being printed, otherwise
 * it would pass no matter how broken the app is.
 */

/**
 * Bundled libraries we have not updated. Their own deprecations are not ours to fix here
 * and would otherwise hide real findings. Anything in our own code still fails.
 */
export const VENDORED = [
  '/includes/plugin_security/htmlpurifier/',
  '/includes/plugin_pdf/dompdf/',
  '/includes/plugin_encrypt/phpseclib/',
  '/includes/plugin_email/class.',
  '/includes/plugin_paymethod_',
  '/includes/plugin_calendar/wdCalendar/',
  '/includes/plugin_captcha/inc/',
  '/includes/plugin_ticket/cron/mime_parser.php',
  '/includes/plugin_file/upload/plupload/',
];

export const LEVELS = 'Warning|Notice|Deprecated|Fatal error|Parse error|Recoverable fatal error';
// PHP prints these as "<b>Warning</b>:  message in <b>/path</b> on line <b>12</b>" with
// html_errors on, and as plain text without it. Both always carry " on line ".
export const DIAGNOSTIC = new RegExp(`<b>(?:${LEVELS})</b>:|(?:^|\\n)(?:${LEVELS}):\\s.*\\son line\\s`, 'm');

/**
 * Diagnostics in our own code, one readable line each.
 *
 * Both shapes PHP prints are matched, and both end in "... in <file> on line <n>". That
 * tail is required, so the app's own copy - pages that legitimately say "Warning: full
 * backups will not work" - is not mistaken for one. Bundled libraries are skipped.
 */
export function describeDiagnostics(body: string): string[] {
  const found: string[] = [];
  const patterns = [
    new RegExp(`<b>(${LEVELS})</b>:\\s*(.{0,250}?) in <b>(.{0,250}?)</b> on line <b>(\\d+)</b>`, 'g'),
    new RegExp(`(?:^|\\n)(${LEVELS}):\\s*(.{0,250}?) in (\\S.{0,250}?) on line (\\d+)`, 'g'),
  ];
  for (const re of patterns) {
    let match;
    while ((match = re.exec(body)) !== null) {
      const file = match[3].replace(/<[^>]*>/g, '').trim();
      if (VENDORED.some(path => file.includes(path))) {
        continue;
      }
      const message = match[2].replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim();
      found.push(`${match[1]}: ${message} in ${file} on line ${match[4]}`);
    }
  }
  return [...new Set(found)];
}


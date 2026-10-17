<?php
/**
 * The public demo's bar: says this is a demo, switches theme, and links to the
 * documentation, GitHub and the store.
 *
 * module_theme::include_ucm() picks custom/design_header.php ahead of the real header, so
 * this file renders the real one itself and slips the bar in after its <body> tag. It only
 * does that in demo mode; otherwise it renders the real header and nothing else.
 *
 * Picking a theme needs nothing here. module_theme::get_current_theme() reads demo_theme
 * in demo mode and keeps the choice in the visitor's session, so the stored theme is left
 * alone and each visitor sees their own.
 */

// The header for the active theme, found the way include_ucm() finds it, minus custom/ -
// which is this file.
$ucm_demo_header = 'design_header.php';
$ucm_demo_candidates = array();
if ( get_display_mode() == 'mobile' ) {
	$ucm_demo_candidates[] = 'mobile/' . $ucm_demo_header;
}
$ucm_demo_theme_dir = basename( (string) module_theme::$current_theme );
if ( strlen( $ucm_demo_theme_dir ) > 2 && is_dir( 'includes/plugin_theme/themes/' . $ucm_demo_theme_dir . '/' ) ) {
	$ucm_demo_candidates[] = 'includes/plugin_theme/themes/' . $ucm_demo_theme_dir . '/' . $ucm_demo_header;
}
if ( module_theme::$current_theme_settings && isset( module_theme::$current_theme_settings['base_dir'] ) && strlen( module_theme::$current_theme_settings['base_dir'] ) > 2 && is_dir( module_theme::$current_theme_settings['base_dir'] ) ) {
	$ucm_demo_candidates[] = module_theme::$current_theme_settings['base_dir'] . $ucm_demo_header;
}
$ucm_demo_candidates[] = $ucm_demo_header;
foreach ( $ucm_demo_candidates as $ucm_demo_file ) {
	if ( is_file( $ucm_demo_file ) ) {
		$ucm_demo_header = $ucm_demo_file;
		break;
	}
}

// Present is not the same as on: ./scripts/demo-mode.sh switches demo mode, and the bar
// has to follow it or the switch does not mean anything.
if ( ! _DEMO_MODE ) {
	include( $ucm_demo_header );

	return;
}

// Each visitor's theme lives in their session, so a cached page would show someone else's.
header( 'Cache-Control: no-store, no-cache, must-revalidate' );
header( 'Pragma: no-cache' );

// Take back exactly the buffer opened here. Draining every level would also swallow the
// one PHP opened for the request on a host with output_buffering on.
$ucm_demo_ob_level = ob_get_level();
ob_start();
include( $ucm_demo_header );
$ucm_demo_header_html = '';
while ( ob_get_level() > $ucm_demo_ob_level ) {
	$ucm_demo_header_html = ob_get_clean() . $ucm_demo_header_html;
}

$ucm_demo_themes = array(
	'pro'            => 'Default',
	'metis'          => 'Metis',
	'theme_adminlte' => 'AdminLTE',
	'whitelabel1'    => 'WhiteLabel',
);
if ( is_dir( 'includes/plugin_theme_blocks/' ) ) {
	$ucm_demo_themes['theme_blocks'] = 'Blocks';
}
$ucm_demo_current = module_theme::get_current_theme();
$ucm_demo_current_name = isset( $ucm_demo_themes[ $ucm_demo_current ] ) ? $ucm_demo_themes[ $ucm_demo_current ] : 'Default';

// This page's address with demo_theme set, so picking a theme keeps you where you are.
$ucm_demo_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
$ucm_demo_path = (string) parse_url( $ucm_demo_uri, PHP_URL_PATH );
$ucm_demo_query = array();
parse_str( (string) parse_url( $ucm_demo_uri, PHP_URL_QUERY ), $ucm_demo_query );
unset( $ucm_demo_query['demo_theme'] );

$ucm_demo_hidden = isset( $_COOKIE['ucm_demo_hidden'] ) && $_COOKIE['ucm_demo_hidden'] === '1';
$ucm_demo_assets = rtrim( (string) _BASE_HREF, '/' ) . '/custom/demo/';

ob_start();
?>
<!-- start theme demo code - copyright @dtbaker -->
<style>
#ucm-demo{position:fixed;top:0;left:0;right:0;z-index:1035;box-sizing:border-box;display:flex;align-items:center;gap:14px;height:46px;padding:0 14px;background:#1f2326;color:#d6d9dc;font:14px/1.2 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;text-align:left}
#ucm-demo *{box-sizing:border-box}
#ucm-demo a{color:#d6d9dc;text-decoration:none}
#ucm-demo a:hover{color:#fff}
#ucm-demo .ucm-demo-tag{background:#3b6d11;color:#eaf3de;border-radius:4px;padding:3px 8px;font-size:12px;font-weight:600;letter-spacing:.02em;white-space:nowrap}
#ucm-demo .ucm-demo-about{color:#9aa1a6;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#ucm-demo .ucm-demo-gap{flex:1}
#ucm-demo .ucm-demo-link{display:inline-flex;align-items:center;gap:6px;white-space:nowrap}
#ucm-demo svg{width:16px;height:16px;fill:currentColor;flex:none}
#ucm-demo .ucm-demo-buy{background:#5b9b2a;color:#fff;border-radius:4px;padding:7px 14px;font-weight:600;white-space:nowrap}
#ucm-demo .ucm-demo-buy:hover{background:#4c8722;color:#fff}
#ucm-demo .ucm-demo-close{display:inline-flex;padding:6px;border-radius:4px;color:#9aa1a6}
#ucm-demo .ucm-demo-close:hover{background:#2e3337}
#ucm-demo details{position:relative}
#ucm-demo summary{display:inline-flex;align-items:center;gap:8px;list-style:none;cursor:pointer;border:1px solid #454b50;border-radius:4px;padding:6px 10px;color:#fff;white-space:nowrap;user-select:none}
#ucm-demo summary::-webkit-details-marker{display:none}
#ucm-demo summary:hover,#ucm-demo details[open] summary{border-color:#6b7277;background:#2a2f33}
#ucm-demo summary .ucm-demo-caret{border:4px solid transparent;border-top-color:currentColor;margin-top:4px}
#ucm-demo .ucm-demo-menu{position:absolute;top:calc(100% + 6px);left:0;display:grid;grid-template-columns:repeat(5,148px);gap:10px;padding:12px;background:#2a2f33;border:1px solid #454b50;border-radius:6px;box-shadow:0 10px 30px rgba(0,0,0,.35)}
#ucm-demo .ucm-demo-menu a{display:block;text-align:center;font-size:13px;color:#d6d9dc;border-radius:4px;padding:4px}
#ucm-demo .ucm-demo-menu img{display:block;width:140px;height:88px;object-fit:cover;object-position:top left;border-radius:3px;border:1px solid #454b50;margin:0 auto 6px;background:#3a4044}
#ucm-demo .ucm-demo-menu a:hover img{border-color:#9aa1a6}
#ucm-demo .ucm-demo-menu a.current{color:#a8d77d;font-weight:600}
#ucm-demo .ucm-demo-menu a.current img{border-color:#7fbf45;outline:2px solid #7fbf45}
#ucm-demo.loading summary .ucm-demo-name::after{content:"\2026"}
#ucm-demo-show{display:none;position:fixed;top:0;left:50%;transform:translateX(-50%);z-index:1035;background:#1f2326;color:#d6d9dc;border-radius:0 0 6px 6px;padding:5px 12px;font:12px/1.2 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;text-decoration:none;box-shadow:0 2px 8px rgba(0,0,0,.25)}
#ucm-demo-show:hover{color:#fff}
body:not(.ucm-demo-hidden){margin-top:46px!important}
body:not(.ucm-demo-hidden)>#holder{top:46px}
body.theme-adminlte.fixed:not(.ucm-demo-hidden) .header{top:46px}
body.theme-adminlte.fixed:not(.ucm-demo-hidden) .left-side{top:96px}
body.ucm-demo-hidden #ucm-demo{display:none}
body.ucm-demo-hidden #ucm-demo-show{display:block}
@media (max-width:1100px){#ucm-demo .ucm-demo-about{display:none}}
@media (max-width:860px){#ucm-demo .ucm-demo-menu{grid-template-columns:repeat(2,148px)}}
@media (max-width:640px){
#ucm-demo{gap:8px;padding:0 8px}
#ucm-demo .ucm-demo-link span{display:none}
#ucm-demo .ucm-demo-label{display:none}
#ucm-demo .ucm-demo-menu{position:fixed;top:52px;left:8px;right:8px;grid-template-columns:repeat(2,1fr)}
#ucm-demo .ucm-demo-menu img{width:100%;height:auto;aspect-ratio:140/88}
}
</style>
<div id="ucm-demo" role="region" aria-label="Demo options">
	<span class="ucm-demo-tag">DEMO</span>
	<details>
		<summary><span class="ucm-demo-label">Theme:</span> <span class="ucm-demo-name"><?php echo htmlspecialchars( $ucm_demo_current_name ); ?></span><span class="ucm-demo-caret" aria-hidden="true"></span></summary>
		<div class="ucm-demo-menu">
			<?php foreach ( $ucm_demo_themes as $ucm_demo_id => $ucm_demo_name ) {
				$ucm_demo_href = $ucm_demo_path . '?' . http_build_query( array_merge( $ucm_demo_query, array( 'demo_theme' => $ucm_demo_id ) ) );
				?>
				<a href="<?php echo htmlspecialchars( $ucm_demo_href ); ?>" class="ucm-demo-theme<?php echo $ucm_demo_id === $ucm_demo_current ? ' current' : ''; ?>"<?php echo $ucm_demo_id === $ucm_demo_current ? ' aria-current="true"' : ''; ?>>
					<img src="<?php echo htmlspecialchars( $ucm_demo_assets . 'theme-' . $ucm_demo_id . '.png' ); ?>" alt="" loading="lazy" width="140" height="88">
					<?php echo htmlspecialchars( $ucm_demo_name ); ?>
				</a>
			<?php } ?>
		</div>
	</details>
	<span class="ucm-demo-about">You're trying Ultimate Client Manager. Every theme here is included.</span>
	<span class="ucm-demo-gap"></span>
	<a class="ucm-demo-link" href="https://ultimateclientmanager.com/docs/" target="_blank" rel="noopener" title="Documentation"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3h11a3 3 0 0 1 3 3v15H7a3 3 0 0 1-3-3V4a1 1 0 0 1 1-1zm1 15a1 1 0 0 0 1 1h10v-2H7a1 1 0 0 0-1 1zm0-3.8c.3-.1.6-.2 1-.2h10V6a1 1 0 0 0-1-1H6z"/></svg><span>Docs</span></a>
	<a class="ucm-demo-link" href="https://github.com/dtbaker/ucm" target="_blank" rel="noopener" title="Download from GitHub"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 .5a11.5 11.5 0 0 0-3.6 22.4c.6.1.8-.3.8-.6v-2c-3.2.7-3.9-1.5-3.9-1.5-.5-1.3-1.3-1.7-1.3-1.7-1-.7.1-.7.1-.7 1.2.1 1.8 1.2 1.8 1.2 1 1.8 2.8 1.3 3.5 1 .1-.8.4-1.3.7-1.6-2.6-.3-5.3-1.3-5.3-5.7 0-1.3.5-2.3 1.2-3.1-.1-.3-.5-1.5.1-3.1 0 0 1-.3 3.2 1.2a11 11 0 0 1 5.8 0c2.2-1.5 3.2-1.2 3.2-1.2.6 1.6.2 2.8.1 3.1.8.8 1.2 1.9 1.2 3.1 0 4.4-2.7 5.4-5.3 5.7.4.4.8 1.1.8 2.2v3.2c0 .3.2.7.8.6A11.5 11.5 0 0 0 12 .5z"/></svg><span>Download from GitHub</span></a>
	<a class="ucm-demo-buy" href="https://codecanyon.net/item/ultimate-client-manager-crm-pro-edition/2621629?ref=dtbaker" target="_blank" rel="noopener">Buy now</a>
	<a class="ucm-demo-close" href="#" id="ucm-demo-hide" title="Close demo options" aria-label="Close demo options"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.4 5 5 6.4 10.6 12 5 17.6 6.4 19l5.6-5.6 5.6 5.6 1.4-1.4-5.6-5.6L19 6.4 17.6 5 12 10.6z"/></svg></a>
</div>
<a href="#" id="ucm-demo-show">Show demo options</a>
<script>
(function () {
	var bar = document.getElementById('ucm-demo');
	var menu = bar.querySelector('details');
	function setHidden(hidden) {
		document.body.classList.toggle('ucm-demo-hidden', hidden);
		document.cookie = 'ucm_demo_hidden=' + (hidden ? '1' : '0') + '; path=/; max-age=31536000; SameSite=Lax';
	}
	document.getElementById('ucm-demo-hide').addEventListener('click', function (e) { e.preventDefault(); setHidden(true); });
	document.getElementById('ucm-demo-show').addEventListener('click', function (e) { e.preventDefault(); setHidden(false); });
	document.addEventListener('click', function (e) { if (menu.open && !menu.contains(e.target)) { menu.open = false; } });
	document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { menu.open = false; } });
	Array.prototype.forEach.call(bar.querySelectorAll('.ucm-demo-theme'), function (a) {
		a.addEventListener('click', function () { bar.classList.add('loading'); menu.open = false; });
	});
})();
</script>
<?php
$ucm_demo_bar = ob_get_clean();

// The bar goes straight after <body>. The class goes on <body> itself, so a closed bar
// stays closed from the first paint rather than flashing open until the script runs.
$ucm_demo_header_html = preg_replace_callback(
	'#<body([^>]*)>#i',
	function ( $m ) use ( $ucm_demo_bar, $ucm_demo_hidden ) {
		$attributes = $m[1];
		if ( $ucm_demo_hidden ) {
			if ( preg_match( '#\bclass\s*=\s*(["\'])#i', $attributes ) ) {
				$attributes = preg_replace( '#\bclass\s*=\s*(["\'])#i', 'class=$1ucm-demo-hidden ', $attributes, 1 );
			} else {
				$attributes .= ' class="ucm-demo-hidden"';
			}
		}

		return '<body' . $attributes . '>' . $ucm_demo_bar;
	},
	$ucm_demo_header_html,
	1
);
echo $ucm_demo_header_html;

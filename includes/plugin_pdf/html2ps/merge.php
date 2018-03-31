<?php

$string = "
require_once(HTML2PS_DIR . 'utils_array.php');
require_once(HTML2PS_DIR . 'utils_graphic.php');
require_once(HTML2PS_DIR . 'utils_url.php');
require_once(HTML2PS_DIR . 'utils_text.php');
require_once(HTML2PS_DIR . 'utils_units.php');
require_once(HTML2PS_DIR . 'utils_number.php');

require_once(HTML2PS_DIR . 'value.color.php');

require_once(HTML2PS_DIR . 'config.parse.php');

require_once(HTML2PS_DIR . 'flow_context.class.inc.php');
require_once(HTML2PS_DIR . 'flow_viewport.class.inc.php');

require_once(HTML2PS_DIR . 'output._interface.class.php');
require_once(HTML2PS_DIR . 'output._generic.class.php');
require_once(HTML2PS_DIR . 'output._generic.pdf.class.php');
require_once(HTML2PS_DIR . 'output._generic.ps.class.php');
require_once(HTML2PS_DIR . 'output.pdflib.old.class.php');
require_once(HTML2PS_DIR . 'output.pdflib.1.6.class.php');
require_once(HTML2PS_DIR . 'output.fpdf.class.php');
require_once(HTML2PS_DIR . 'output.fastps.class.php');
require_once(HTML2PS_DIR . 'output.fastps.l2.class.php');
require_once(HTML2PS_DIR . 'output.png.class.php');

require_once(HTML2PS_DIR . 'stubs.common.inc.php');

require_once(HTML2PS_DIR . 'media.layout.inc.php');

require_once(HTML2PS_DIR . 'box.php');
require_once(HTML2PS_DIR . 'box.generic.php');
require_once(HTML2PS_DIR . 'box.generic.formatted.php');
require_once(HTML2PS_DIR . 'box.container.php');
require_once(HTML2PS_DIR . 'box.generic.inline.php');
require_once(HTML2PS_DIR . 'box.inline.php');
require_once(HTML2PS_DIR . 'box.inline.control.php');

require_once(HTML2PS_DIR . 'font.class.php');
require_once(HTML2PS_DIR . 'font_factory.class.php');

require_once(HTML2PS_DIR . 'box.br.php');
require_once(HTML2PS_DIR . 'box.block.php');
require_once(HTML2PS_DIR . 'box.page.php');
require_once(HTML2PS_DIR . 'box.page.margin.class.php');
require_once(HTML2PS_DIR . 'box.body.php');
require_once(HTML2PS_DIR . 'box.block.inline.php');
require_once(HTML2PS_DIR . 'box.button.php');
require_once(HTML2PS_DIR . 'box.button.submit.php');
require_once(HTML2PS_DIR . 'box.button.reset.php');
require_once(HTML2PS_DIR . 'box.checkbutton.php');
require_once(HTML2PS_DIR . 'box.form.php');
require_once(HTML2PS_DIR . 'box.frame.php');
require_once(HTML2PS_DIR . 'box.iframe.php');
require_once(HTML2PS_DIR . 'box.input.text.php');
require_once(HTML2PS_DIR . 'box.input.textarea.php');
require_once(HTML2PS_DIR . 'box.input.password.php');
require_once(HTML2PS_DIR . 'box.legend.php');
require_once(HTML2PS_DIR . 'box.list-item.php');
require_once(HTML2PS_DIR . 'box.null.php');
require_once(HTML2PS_DIR . 'box.radiobutton.php');
require_once(HTML2PS_DIR . 'box.select.php');
require_once(HTML2PS_DIR . 'box.table.php');
require_once(HTML2PS_DIR . 'box.table.cell.php');
require_once(HTML2PS_DIR . 'box.table.cell.fake.php');
require_once(HTML2PS_DIR . 'box.table.row.php');
require_once(HTML2PS_DIR . 'box.table.section.php');

require_once(HTML2PS_DIR . 'box.text.php');
require_once(HTML2PS_DIR . 'box.text.string.php');
require_once(HTML2PS_DIR . 'box.field.pageno.php');
require_once(HTML2PS_DIR . 'box.field.pages.php');

require_once(HTML2PS_DIR . 'box.whitespace.php');

require_once(HTML2PS_DIR . 'box.img.php'); // Inherited from the text box!
require_once(HTML2PS_DIR . 'box.input.img.php');

require_once(HTML2PS_DIR . 'box.utils.text-align.inc.php');

require_once(HTML2PS_DIR . 'manager.encoding.php');

require_once(HTML2PS_DIR . 'ps.unicode.inc.php');
require_once(HTML2PS_DIR . 'ps.utils.inc.php');
require_once(HTML2PS_DIR . 'ps.whitespace.inc.php');

require_once(HTML2PS_DIR . 'ps.image.encoder.inc.php');
require_once(HTML2PS_DIR . 'ps.image.encoder.simple.inc.php');
require_once(HTML2PS_DIR . 'ps.l2.image.encoder.stream.inc.php');
require_once(HTML2PS_DIR . 'ps.l3.image.encoder.stream.inc.php');

require_once(HTML2PS_DIR . 'tag.body.inc.php');
require_once(HTML2PS_DIR . 'tag.font.inc.php');
require_once(HTML2PS_DIR . 'tag.frame.inc.php');
require_once(HTML2PS_DIR . 'tag.input.inc.php');
require_once(HTML2PS_DIR . 'tag.img.inc.php');
require_once(HTML2PS_DIR . 'tag.select.inc.php');
require_once(HTML2PS_DIR . 'tag.span.inc.php');
require_once(HTML2PS_DIR . 'tag.table.inc.php');
require_once(HTML2PS_DIR . 'tag.td.inc.php');
require_once(HTML2PS_DIR . 'tag.utils.inc.php');

require_once(HTML2PS_DIR . 'tree.navigation.inc.php');

require_once(HTML2PS_DIR . 'html.attrs.inc.php');

require_once(HTML2PS_DIR . 'xhtml.autoclose.inc.php');
require_once(HTML2PS_DIR . 'xhtml.utils.inc.php');
require_once(HTML2PS_DIR . 'xhtml.tables.inc.php');
require_once(HTML2PS_DIR . 'xhtml.p.inc.php');
require_once(HTML2PS_DIR . 'xhtml.lists.inc.php');
require_once(HTML2PS_DIR . 'xhtml.deflist.inc.php');
require_once(HTML2PS_DIR . 'xhtml.script.inc.php');
require_once(HTML2PS_DIR . 'xhtml.entities.inc.php');
require_once(HTML2PS_DIR . 'xhtml.comments.inc.php');
require_once(HTML2PS_DIR . 'xhtml.style.inc.php');
require_once(HTML2PS_DIR . 'xhtml.selects.inc.php');

require_once(HTML2PS_DIR . 'background.image.php');
require_once(HTML2PS_DIR . 'background.position.php');

require_once(HTML2PS_DIR . 'list-style.image.php');

require_once(HTML2PS_DIR . 'height.php');
require_once(HTML2PS_DIR . 'width.php');

require_once(HTML2PS_DIR . 'css.counter.php');
require_once(HTML2PS_DIR . 'css.counter.collection.php');

require_once(HTML2PS_DIR . 'css.colors.inc.php');

require_once(HTML2PS_DIR . 'css.constants.inc.php');
require_once(HTML2PS_DIR . 'css.inc.php');
require_once(HTML2PS_DIR . 'css.state.class.php');
require_once(HTML2PS_DIR . 'css.cache.class.php');
require_once(HTML2PS_DIR . 'css.property.handler.class.php');
require_once(HTML2PS_DIR . 'css.property.stringset.class.php');
require_once(HTML2PS_DIR . 'css.property.sub.class.php');
require_once(HTML2PS_DIR . 'css.property.sub.field.class.php');
require_once(HTML2PS_DIR . 'css.utils.inc.php');
require_once(HTML2PS_DIR . 'css.parse.inc.php');
require_once(HTML2PS_DIR . 'css.parse.media.inc.php');

require_once(HTML2PS_DIR . 'css.background.attachment.inc.php');
require_once(HTML2PS_DIR . 'css.background.color.inc.php');
require_once(HTML2PS_DIR . 'css.background.image.inc.php');
require_once(HTML2PS_DIR . 'css.background.repeat.inc.php');
require_once(HTML2PS_DIR . 'css.background.position.inc.php');
require_once(HTML2PS_DIR . 'css.background.inc.php');

require_once(HTML2PS_DIR . 'css.border.inc.php');
require_once(HTML2PS_DIR . 'css.border.style.inc.php');
require_once(HTML2PS_DIR . 'css.border.collapse.inc.php');
require_once(HTML2PS_DIR . 'css.bottom.inc.php');
require_once(HTML2PS_DIR . 'css.clear.inc.php');
require_once(HTML2PS_DIR . 'css.color.inc.php');
require_once(HTML2PS_DIR . 'css.direction.inc.php');
require_once(HTML2PS_DIR . 'css.html2ps.html.content.inc.php');
require_once(HTML2PS_DIR . 'css.html2ps.pseudoelements.inc.php');
require_once(HTML2PS_DIR . 'css.html2ps.pixels.php');
require_once(HTML2PS_DIR . 'css.content.inc.php');
require_once(HTML2PS_DIR . 'css.display.inc.php');
require_once(HTML2PS_DIR . 'css.float.inc.php');
require_once(HTML2PS_DIR . 'css.font.inc.php');
require_once(HTML2PS_DIR . 'css.height.inc.php');
require_once(HTML2PS_DIR . 'css.min-height.inc.php');
require_once(HTML2PS_DIR . 'css.max-height.inc.php');
require_once(HTML2PS_DIR . 'css.left.inc.php');
require_once(HTML2PS_DIR . 'css.letter-spacing.inc.php');

require_once(HTML2PS_DIR . 'css.list-style-image.inc.php');
require_once(HTML2PS_DIR . 'css.list-style-position.inc.php');
require_once(HTML2PS_DIR . 'css.list-style-type.inc.php');
require_once(HTML2PS_DIR . 'css.list-style.inc.php');

require_once(HTML2PS_DIR . 'css.margin.inc.php');
require_once(HTML2PS_DIR . 'css.overflow.inc.php');
require_once(HTML2PS_DIR . 'css.padding.inc.php');

require_once(HTML2PS_DIR . 'css.page.inc.php');
require_once(HTML2PS_DIR . 'css.page-break.inc.php');
require_once(HTML2PS_DIR . 'css.page-break-after.inc.php');
require_once(HTML2PS_DIR . 'css.page-break-before.inc.php');
require_once(HTML2PS_DIR . 'css.page-break-inside.inc.php');
require_once(HTML2PS_DIR . 'css.orphans.inc.php');
require_once(HTML2PS_DIR . 'css.size.inc.php');
require_once(HTML2PS_DIR . 'css.widows.inc.php');

require_once(HTML2PS_DIR . 'css.position.inc.php');
require_once(HTML2PS_DIR . 'css.right.inc.php');
require_once(HTML2PS_DIR . 'css.property.declaration.php');
require_once(HTML2PS_DIR . 'css.rules.inc.php');
require_once(HTML2PS_DIR . 'css.ruleset.class.php');
require_once(HTML2PS_DIR . 'css.selectors.inc.php');
require_once(HTML2PS_DIR . 'css.table-layout.inc.php');
require_once(HTML2PS_DIR . 'css.text-align.inc.php');
require_once(HTML2PS_DIR . 'css.text-decoration.inc.php');
require_once(HTML2PS_DIR . 'css.text-transform.inc.php');
require_once(HTML2PS_DIR . 'css.text-indent.inc.php');
require_once(HTML2PS_DIR . 'css.top.inc.php');
require_once(HTML2PS_DIR . 'css.vertical-align.inc.php');
require_once(HTML2PS_DIR . 'css.visibility.inc.php');
require_once(HTML2PS_DIR . 'css.white-space.inc.php');
require_once(HTML2PS_DIR . 'css.width.inc.php');
require_once(HTML2PS_DIR . 'css.word-spacing.inc.php');
require_once(HTML2PS_DIR . 'css.z-index.inc.php');

require_once(HTML2PS_DIR . 'css.pseudo.add.margin.inc.php');
require_once(HTML2PS_DIR . 'css.pseudo.align.inc.php');
require_once(HTML2PS_DIR . 'css.pseudo.cellspacing.inc.php');
require_once(HTML2PS_DIR . 'css.pseudo.cellpadding.inc.php');
require_once(HTML2PS_DIR . 'css.pseudo.form.action.inc.php');
require_once(HTML2PS_DIR . 'css.pseudo.form.radiogroup.inc.php');
require_once(HTML2PS_DIR . 'css.pseudo.link.destination.inc.php');
require_once(HTML2PS_DIR . 'css.pseudo.link.target.inc.php');
require_once(HTML2PS_DIR . 'css.pseudo.listcounter.inc.php');
require_once(HTML2PS_DIR . 'css.pseudo.localalign.inc.php');
require_once(HTML2PS_DIR . 'css.pseudo.nowrap.inc.php');
require_once(HTML2PS_DIR . 'css.pseudo.table.border.inc.php');

// After all CSS utilities and constants have been initialized, load the default (precomiled) CSS stylesheet
require_once(HTML2PS_DIR . 'converter.class.php');
require_once(HTML2PS_DIR . 'treebuilder.class.php');
require_once(HTML2PS_DIR . 'image.class.php');

require_once(HTML2PS_DIR . 'fetched_data._interface.class.php');
require_once(HTML2PS_DIR . 'fetched_data._html.class.php');
require_once(HTML2PS_DIR . 'fetched_data.url.class.php');
require_once(HTML2PS_DIR . 'fetched_data.file.class.php');

require_once(HTML2PS_DIR . 'filter.data._interface.class.php');
require_once(HTML2PS_DIR . 'filter.data.doctype.class.php');

require_once(HTML2PS_DIR . 'filter.data.utf8.class.php');
require_once(HTML2PS_DIR . 'filter.data.ucs2.class.php');

require_once(HTML2PS_DIR . 'filter.data.html2xhtml.class.php');
require_once(HTML2PS_DIR . 'filter.data.xhtml2xhtml.class.php');

require_once(HTML2PS_DIR . 'parser._interface.class.php');
require_once(HTML2PS_DIR . 'parser.xhtml.class.php');

require_once(HTML2PS_DIR . 'filter.pre._interface.class.php');
require_once(HTML2PS_DIR . 'filter.pre.fields.class.php');
require_once(HTML2PS_DIR . 'filter.pre.headfoot.class.php');
require_once(HTML2PS_DIR . 'filter.pre.footnotes.class.php');
require_once(HTML2PS_DIR . 'filter.pre.height-constraint.class.php');

require_once(HTML2PS_DIR . 'layout._interface.class.php');
require_once(HTML2PS_DIR . 'layout.default.class.php');
require_once(HTML2PS_DIR . 'layout.page.breaks.php');

require_once(HTML2PS_DIR . 'filter.post._interface.class.php');
require_once(HTML2PS_DIR . 'filter.post.positioned.class.php');
require_once(HTML2PS_DIR . 'filter.post.postponed.class.php');

require_once(HTML2PS_DIR . 'filter.output._interface.class.php');
require_once(HTML2PS_DIR . 'filter.output.ps2pdf.class.php');
require_once(HTML2PS_DIR . 'filter.output.gzip.class.php');

require_once(HTML2PS_DIR . 'destination._interface.class.php');
require_once(HTML2PS_DIR . 'destination._http.class.php');
require_once(HTML2PS_DIR . 'destination.browser.class.php');
require_once(HTML2PS_DIR . 'destination.download.class.php');
require_once(HTML2PS_DIR . 'destination.file.class.php');

require_once(HTML2PS_DIR . 'xml.validation.inc.php');

require_once(HTML2PS_DIR . 'content_type.class.php');
require_once(HTML2PS_DIR . 'dispatcher.class.php');
require_once(HTML2PS_DIR . 'observer.class.php');

require_once(HTML2PS_DIR . 'strategy.page.break.simple.php');
require_once(HTML2PS_DIR . 'strategy.page.break.smart.php');

require_once(HTML2PS_DIR . 'strategy.link.rendering.normal.php');
require_once(HTML2PS_DIR . 'strategy.position.absolute.php');
require_once(HTML2PS_DIR . 'strategy.width.absolute.positioned.php');
require_once(HTML2PS_DIR . 'autofix.url.php');

require_once(HTML2PS_DIR . 'fetcher._interface.class.php');
";

$bits   = explode( "\n", $string );
$merged = '';
foreach ( $bits as $b ) {
	$b = trim( $b );
	if ( ! $b ) {
		continue;
	}
	if ( preg_match( "#'([^']+\.php)'#", $b, $matches ) ) {
		echo $matches[1] . '<br>';
		if ( is_file( $matches[1] ) ) {
			$content = file_get_contents( $matches[1] );
			rename( $matches[1], 'merged/' . $matches[1] );
			$merged .= $content;
		}
	}
}
file_put_contents( 'pipeline.merged.php', $merged );
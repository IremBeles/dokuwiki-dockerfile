<?php
/**
 * DokuWiki Syntax Plugin Medialist
 *
 * Show a list of media files (images/archives ...) referred in a given page
 * or stored in a given namespace.
 *
 * Syntax:  {{medialist>[id]}}
 *          {{medialist>[ns]:}} or {{medialist>[ns]:*}}
 *
 *   [id] - a valid page id (use @ID@ for the current page)
 *   [ns] - a namespace (use @NS@: for the current namespace)
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Michael Klier <chi@chimeric.de>
 * @author  Satoshi Sahara <sahara.satoshi@gmail.com>
 */
// must be run within DokuWiki
if(!defined('DOKU_INC')) die();

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_medialist extends DokuWiki_Syntax_Plugin {

    protected $pattern = '{{medialist>[^\r\n]+?}}';

    function getType()  { return 'substition'; }
    function getPType() { return 'block'; }
    function getSort()  { return 299; }

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern($this->pattern, $mode, 'plugin_medialist');
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, Doku_Handler $handler) {
        return array($state, $match);
    }

    /**
     * Create output
     */
    function render($format, Doku_Renderer $renderer, $data) {
        global $ACT;

        list($state, $match) = $data;

        $medialist = $this->loadHelper('medialist');
        $params = $medialist->parse($match);

        switch ($format) {
            case 'xhtml':
                if (in_array($ACT, array('preview'))) {
                    $renderer->doc .= $medialist->render_xhtml($params);
                } else {
                    // output placeholder which will be replaced in action component
                    $renderer->doc .= '<!-- MEDIALIST '. substr($match, 12, -2) .' -->'.DOKU_LF;

                    // another implementation: reqires disabling xhtml cache of whole page...
                    //$renderer->info['cache'] = false; // rendered result may not cached
                    //$renderer->doc .= $medialist->render_xhtml($params);
                }
                return true;
            case 'metadata':
                return false;
        }
        return false;
    }

}

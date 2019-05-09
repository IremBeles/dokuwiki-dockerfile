<?php
/**
 * Helper Component of Medialist plugin
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Satoshi Sahara <sahara.satoshi@gmail.com>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

class helper_plugin_medialist extends DokuWiki_Plugin {

    /**
     * syntax parser
     *
     * @param $data string matched the regex {{medialist>[^\r\n]+?}}
     * @return array parameter for render process
     *
     * -----------------------------------------------------------------------
     * [ns:]   namespace, must end ":" or ":*"
     * [page]  page id
     * 
     *  {{pagelist>ns:}}
     *       - show media files in the given namespace
     *       - show button to open the given ns by fullscreen media manager
     *  {{pagelist>ns: page}}
     *       - distinguish linked files in the page if they found in the list
     *  {{pagelist>ns: +page}}
     *       - add linked media files to the list (@BOTH@)
     * 
     *  {{pagelist>page}}
     *       - show media files linked in the given page
     *  {{pagelist>page ns:}}
     *       - show button to open the given ns by fullscreen media manager
     * -----------------------------------------------------------------------
     */
    public function parse($data) {
        global $INFO;

        $params = array(); // parameter array for render process

        $match = substr($data, 12, -2);
        $match = str_replace('  ',' ', trim($match)); // remove excessive white spaces

        // v1 syntax (backword compatibility for 2009-05-21 release)
        // @PAGE@, @NAMESPACE@, @ALL@ are complete keyword arguments,
        // not replacement patterns.
        switch ($match) {
            case '@PAGE@':
                $match = '@ID@';  break;
            case '@NAMESPACE@':
                $match = '@NS@:*'; break;
            case '@ALL@':
            case '@BOTH@':
                $match = '@NS@:* +@ID@'; break;
        }

        // v2 syntax (available since 2016-06-XX release)
        // - enable replacement patterns @ID@, @NS@, @PAGE@
        //   for media file search scope
        // - Namespace search if scope parameter ends colon ":", and
        //   require "*" after the colon for recursive search

        // replacement patterns identical with Namespace Template
        // @see https://www.dokuwiki.org/namespace_templates#syntax
        $args = $match;
        $args = str_replace('@ID@', $INFO['id'], $args);
        $args = str_replace('@NS@', getNS($INFO['id']), $args);
        $args = str_replace('@PAGE@', noNS($INFO['id']), $args);

        $args = explode(' ', $args, 2);

        // check first parameter
        if (substr($args[0], -1) == ':') {
                $params['ns'] = substr($args[0], 0, -1);
                $params['depth'] = 1;  // set depth option for search()
        } elseif (substr($args[0], -2) == ':*') {
                $params['ns'] = substr($args[0], 0, -2);
        } else {
                $params['page'] = $args[0];
        }

        // check second parameter
        if (!empty($args[1])) {
            if (isset($params['ns'])) {
                $params['page'] = ltrim($args[1], '+');
                $params['append'] = (bool) ($args[1][0] == '+');
            } else {
                $params['uploadns'] = rtrim($args[1],':');
            }
        }
        return $params;
   }


    /**
     * Renders xhtml
     */
    public function render_xhtml($params) {

        $linked_media = array();
        $stored_media = array();

        // search internal files in the given namespace
        if (isset($params['ns'])) {
            // search option for lookup_stored_media()
            $opt = array();
            if (array_key_exists('depth', $params)) {
                $opt = $opt + array('depth' => $params['depth']);
            }
            $stored_media = $this->_lookup_stored_media($params['ns'], $opt);
        }

        // search linked/used media in the given page
        if (isset($params['page'])) {
            $linked_media = $this->_lookup_linked_media($params['page']);
        }

        if (isset($params['append']) && $params['append']) {
            $media = array_unique(array_merge($stored_media, $linked_media), SORT_REGULAR);
        } else {
            $media = isset($params['ns']) ? $stored_media : $linked_media;
            if (!$params['ns'] && $params['page']) {
                $linked_media = array();
            }
        }

        // prepare list items
        $items = array();
        foreach ($media as $item) {
            $base = !isset($params['ns']) ? getNS($item['id']) : $params['ns'];

            if (in_array($item, $linked_media)) {
                $item = $item + array('level' => 1, 'base' => $base, 'linked'=> 1);
            } else {
                $item = $item + array('level' => 1, 'base' => $base);
            }
            $items[] = $item;
        }

        // create output
        $out  = '';
        $out .= '<div class="medialist">'. DOKU_LF;

        // mediamanager button
        $uploadns = isset($params['uploadns']) ? $params['uploadns'] : $params['ns'];
        $tab = empty($items) ? 'upload' : 'files';
        if (isset($uploadns) && (auth_quickaclcheck("$uploadns:*") >= AUTH_UPLOAD)) {
            $out .= '<div class="mediamanager">';
            $out .= $this->_mediamanager_button($uploadns, $tab);
            $out .= '</div>'. DOKU_LF;
        }

        // list of media files
        if (!empty($items)) {
            $out .= html_buildlist($items, 'medialist', array($this, '_media_item'));
            $out .= DOKU_LF;
        } elseif ($this->getConf('emptyInfo')) {
            $out .= '<div class="info">';
            $out .= '<strong>'.$this->getPluginName().'</strong>'.': nothing to show here.';
            $out .= '</div>'. DOKU_LF;;
        }
        $out .= '</div>'. DOKU_LF;
        return $out;
    }

    /**
     * Callback function for html_buildlist()
     *
     * @author Michael Klier <chi@chimeric.de>
     */
    public function _media_item($item) {
        global $conf, $lang;

        $out = '';

        $link = array();
        $link['url']    = ml($item['id']);
        $link['class']  = isset($item['linked']) ? 'media linked' : 'media';
        $link['target'] = $conf['target']['media'];
        $link['title']  = noNS($item['id']);

        // link text and mediainfo
        if ($item['type'] == 'internalmedia') {
            // Internal file
            if (!empty($item['base'])) {
                // remove base namespace to get shorten link text
                $link['name'] = preg_replace('/^'.$item['base'].':/','', $item['id']);
            } else {
                $link['name'] = $item['id'];
            }
            $mediainfo  = strftime($conf['dformat'], $item['mtime']).'&nbsp;';
            $mediainfo .= filesize_h($item['size']);
        } else {
            // External link
            $link['name'] = $item['id'];
            $mediainfo = $lang['qb_extlink']; // External Link
        }

        // add file icons
        list($ext,$mime) = mimetype($item['id']);
        $class = preg_replace('/[^_\-a-z0-9]+/i','_',$ext);
        $link['class'] .= ' mediafile mf_'.$class;

        // build the list item
        if ($this->getConf('checkboxes')) {
            $out .= '<input type="checkbox" id="delete['.$item['id'].']" />';
            $out .= '<label for="delete['.$item['id'].']">'.'</label>';
        }
        $out .= '<a href="' . $link['url'] . '" ';
        $out .= 'class="' . $link['class'] . '" ';
        $out .= 'target="' . $link['target'] . '" ';
        $out .= 'title="' . $link['title'] . '">';
        $out .= $link['name'];
        $out .= '</a>';
        $out .= '&nbsp;<span class="mediainfo">('.$mediainfo.')</span>' . DOKU_LF;

        return $out;
    }

    /**
     * button to open a given namespace with the Fullscreen Media Manager
     * @param $ns  string namespace
     * @param $tab string tab name of MediaManager (files|upload|search)
     * @return string html
     */
    protected function _mediamanager_button($ns, $tab=null) {
        global $INFO, $lang;

        $method  = 'get';
        $params  = array('do' => 'media', 'ns' => $ns);
        if (in_array($tab, array('files','upload','search'))) {
            $params += array('tab_files' => $tab);
        }
        $label   = hsc("$ns:*");
        $tooltip = ($tab == 'upload') ? $lang['btn_upload'] :$lang['btn_media'];
        $accesskey = '';
        return html_btn('media', $INFO['id'], $accesskey, $params, $method, $tooltip, $label);
    }


    /**
     * searches media files linked in the given page
     * returns an array of items
     */
    protected function _lookup_linked_media($id) {
        $linked_media = array();

        if (!page_exists($id)) {
            //msg('MediaList: page "'. hsc($id) . '" not exists!', -1); 
        }

        if (auth_quickaclcheck($id) >= AUTH_READ) {
            // get the instructions
            $ins = p_cached_instructions(wikiFN($id), true, $id);

            // get linked media files
            foreach ($ins as $node) {
                if ($node[0] == 'internalmedia') {
                    $id = cleanID($node[1][0]);
                    $fn = mediaFN($id);
                    if (!file_exists($fn)) continue;
                    $linked_media[] = array(
                        'id'    => $id,
                        'size'  => filesize($fn),
                        'mtime' => filemtime($fn),
                        'type'  => $node[0],
                    );
                } elseif ($node[0] == 'externalmedia') {
                    $linked_media[] = array(
                        'id'    => $node[1][0],
                        'size'  => null,
                        'mtime' => null,
                        'type'  => $node[0],
                    );
                }
            }

        }
        return array_unique($linked_media, SORT_REGULAR);
    }

    /**
     * searches media files stored in the given namespace and sub-tiers
     * returns an array of items
     */
    protected function _lookup_stored_media($ns, $opt=array('depth'=>1)) {
        global $conf;

        $stored_media = array();

        $dir = utf8_encodeFN(str_replace(':','/', $ns));

        if (!is_dir($conf['mediadir'] . '/' . $dir)) {
            //msg('MediaList: namespace "'. hsc($ns). '" not exists!', -1);
        }

        if (auth_quickaclcheck("$ns:*") >= AUTH_READ) {
            // search media files in the namespace
            $res = array(); // search result
            search($res, $conf['mediadir'], 'search_media', $opt, $dir);

            // prepare return array
            foreach ($res as $item) {
                $stored_media[] = array(
                        'id'    => $item['id'],
                        'size'  => $item['size'],
                        'mtime' => $item['mtime'],
                        'type'  => 'internalmedia',
                );
            }
        }
        return $stored_media;
    }

}


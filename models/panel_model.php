<?php

class PanelModel extends Model {
    /**
     * @var array Stores the current panel
     */
    private $panel;

    /**
     * @var boolean The status specifies whether operation was succeed.
     *  
     */
    private $status;

    public function __construct() {
    }

    /**
     * Fetch the panel info specified by $panel_uuid, and store it in $this->panel
     *
     * @return no return value
     */
    public function panel_info($panel_uuid) {
        $db = new SQLite3('lighting-server.db');
        $res = $db->query("select * from panels where uuid = {$panel_uuid}");

        if(! ($panel = $res->fetchArray(SQLITE3_ASSOC))) {
            // no such panel

            $this->panel = array();
            return ;
        }

        $res = $db->query("select panel_buttons.button_id, panel_buttons.scene_uuid from panels left join panel_buttons on panel_buttons.panel_uuid = panels.uuid where panels.uuid = {$panel_uuid};");

        while(($button = $res->fetchArray(SQLITE3_ASSOC))) {
            $panel['buttons'][] = array('button_id' => "{$button['button_id']}", 'scene_uuid' => "{$button['scene_uuid']}");
        }

        $this->panel = $panel;
    }

    /**
     * Update a panel
     *
     * @param $panel array  The panel's new information
     */
    public function panel_update($panel) {
        $db = new SQLite3('lighting-server.db');
        $source = <<<EOD
update panels set name = '{$panel['name']}', type = {$panel['type']} where uuid = {$panel['uuid']};
EOD;
        $ret = $db->exec($source);

        if(! $ret) {
            $error_code = $db->lastErrorCode();
            $this->status = array(
                'failure' => array(
                    'uri' => "/controllers/{$panel['uuid']}", 
                    'desc' => "error code: {$error_code}"
                )
            );
            return ;
        }

        $source = <<<EOD
select * from panel_buttons where panel_uuid = {$panel['uuid']};
EOD;
        $res = $db->query($source);
        $origin_buttons = array();
        while(($button = $res->fetchArray(SQLITE3_ASSOC))) {
            $origin_buttons[] = $button['button_id'];
        }

        $to_delete = array_diff($origin_buttons, $panel['buttons']);
        $to_insert = array_diff($panel['buttons'], $origin_buttons);

        foreach($to_delete as $button_id) {
            $source = "delete from panel_buttons where button_id = '{$button_id}' and panel_uuid = {$panel['uuid']}";
            $db->exec($source);
        var_dump($source);
        }

        foreach($to_insert as $button_id) {
            $source = "insert into panel_buttons (button_id, panel_uuid, scene_uuid) values ('{$button_id}', {$panel['uuid']}, 0);";

            $ret = $db->exec($source);
        var_dump($source);
        }

        if($ret) {
            $this->status = array(
                'success' => array(
                    'uri' => '/controllers', 
                    'desc' => ""
                )
            );
        }

    }

    /**
     * Build relationships between panel buttons and scenes.
     */
    public function panel_configure($configure) {
        $db = new SQLite3('lighting-server.db');

        foreach($configure['buttons'] as $button) {
            $source = "update panel_buttons set scene_uuid = {$button['scene_uuid']} where button_id = '{$button['button_id']}' and panel_uuid = {$configure['panel_uuid']}";

            $ret = $db->exec($source);

            if(! $ret) {
                $error_code = $db->lastErrorCode();
                $this->status = array(
                    'failure' => array(
                        'uri' => "/controllers/{$configure['panel_uuid']}/configure", 
                        'desc' => "error code: {$error_code}"
                    )
                );

                return ;
            }
        }

        $this->status = array(
            'success' => array(
                'uri' => "/controllers/{$configure['panel_uuid']}/configure", 
                'desc' => ""
            )
        );
    }

    /**
     * Used by PanelView to get the current panel info
     *
     * @return array The panel info
     */
    public function get_panel() {
        return $this->panel;
    }

    /**
     * Used by PanelView to get the last operation's status
     *
     * @return boolean The last operation's status
     */
    public function get_status() {
        return $this->status;
    }
}

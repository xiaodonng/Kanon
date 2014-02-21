<?php

class PanelButtonView extends View {
    private $model;

    public function __construct($model) {
        $this->model = $model;
    }

    public function render_post($params) {
        $status = $this->model->get_status();

        /*
        foreach($status as $key => $value) {
            if($value) {
                $resbody[] = array('success' => array('uri' => "/controllers/{$params[0]}/buttons/{$key}", 'desc' => ""));
            }
        }
         */

        $source = json_encode($status);
        echo $source;
    }
}

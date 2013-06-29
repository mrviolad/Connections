<?php

class ConnectionsActivation {

    public function beforeActivation(&$controller) {
        return true;
    }

    public function onActivation(&$controller) {
        $controller->Croogo->addAco('Connections');
    }

    public function beforeDeactivation(&$controller) {
        return true;
    }

    public function onDeactivation(&$controller) {
        $controller->Croogo->removeAco('Connections');
    }

}

?>

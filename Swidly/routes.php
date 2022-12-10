<?php

/**
 * Created a route for the API
 */
$this->get('/api/getVersion', function($req, $res) {
    echo APP_VERSION;
});
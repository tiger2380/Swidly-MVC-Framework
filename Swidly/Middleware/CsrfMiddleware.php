<?php

namespace Swidly\Middleware;

use Swidly\Core\SwidlyException;

class CsrfMiddleware extends BaseMiddleWare {
    /**
     * @throws SwidlyException
     */
    public function execute($request, $response) {
        if ($request->isPost()) {
            $token = $request->get('csrf');
            if(!\Swidly\Core\Store::verifyCsrf($token)) {
                throw new SwidlyException('Invalid csrf token');
            }

            \Swidly\Core\Store::regenerateCsrf();
        }
    }
}
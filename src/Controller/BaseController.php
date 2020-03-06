<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class BaseController extends AbstractController
{
    protected function errorResponse($errorCode, $errorMessage)
    {
        return $this->json([
            'errors' => [
                [
                    'detail' => $errorMessage,
                ],
            ],
        ], $errorCode);
    }
}

<?php

namespace App\Controller;

use Endroid\QrCode\Builder\BuilderInterface;
use Endroid\QrCodeBundle\Response\QrCodeResponse;
use Symfony\Component\Routing\Annotation\Route;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\Label\Font\NotoSans;

class QrCodeGeneratorController extends AbstractController
{
    #[Route('/qr-code-test', name: 'app_qr_code_test')]
    public function testAction()
    {
        return $this->render('qrCodeGenerator/test.twig');
    }

    #[Route('/qr-code-test-2', name: 'app_qr_code_test2')]
    public function test2Action(BuilderInterface $customQrCodeBuilder)
    {
        $result = $customQrCodeBuilder->build(
            size: 400,
            margin: 20
        );

        return new QrCodeResponse($result);
    }
}

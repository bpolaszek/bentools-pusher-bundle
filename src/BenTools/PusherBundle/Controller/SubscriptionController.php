<?php

namespace BenTools\PusherBundle\Controller;

use BenTools\PusherBundle\Entity\Recipient;
use DeviceDetector\DeviceDetector;
use function GuzzleHttp\json_decode;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class SubscriptionController extends Controller {

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function registerAction(Request $request) {
        $em = $this->container->get('doctrine')->getManagerForClass(Recipient::class);
        $deviceDetector = new DeviceDetector($request->headers->get('User-Agent'));
        $deviceDetector->parse();

        try {

            $data      = json_decode($request->getContent(), true);
            $recipient = Recipient::createFromArray($data['subscription']);

            if ($this->getUser() && is_callable([$this->getUser(), 'getId'])) {
                $recipient->setUserClass(get_class($this->getUser()));
                $recipient->setUserId($this->getUser()->getId());
            }

            if (isset($data['options']) && is_array($data['options'])) {
                $recipient->setOptions($data['options']);
            }

            $recipient->setClient($deviceDetector->getClient('name'));
            $recipient->setDevice($deviceDetector->getDeviceName());

            $em->persist($recipient);
            $em->flush();

            return new JsonResponse([
                'success' => true,
            ], JsonResponse::HTTP_CREATED);

        }
        catch (\Exception $e) {

            return new JsonResponse([
                'success' => false,
                'error'   => $e->getMessage(),
            ], JsonResponse::HTTP_BAD_REQUEST);

        }
    }

}
<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Model\Model;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller {
    private $xml_out;
    private $twig_file_out;
    private $type;

    /**
     * @Route("/", name="homepage")
     */
    public function indexAction() {
        $xml = '';
        $user = '';
        if (isset($_SESSION['secure'])) {
            $xml = $_SESSION['xmlResponse'];
            $user = $_SESSION['user'];
        }
        $params = array(
            'xml' => $xml,
            'user' => $user
        );

        return $this->render('default\index.html.twig', $params);
    }

    public function loginAction(Request $request) {
        if (!$request->isXmlHttpRequest()) {
            return new JsonResponse(array('message' => 'You can access this only using Ajax!'), 400);
        }
        $xml = '';
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            parse_str($_POST['formdata'], $searcharray);
            $params['account'] = $searcharray['account'];
            $params['site_id'] = $searcharray['site_id'];
            $params['site_secure_code'] = $searcharray['site_secure_code'];

            $xml = $this->getXML($params);
            $xmlResponse = simplexml_load_string($xml);
            if ((string) $xmlResponse['result'] == 'error') {
                return $this->logoutAction();
            }
            if (!isset($_SESSION)) {
                session_start();
            }

            $xmlResponse = new \SimpleXMLElement($xml);
            $xxx = $xmlResponse->customer[0]->firstname;
            $_SESSION['xmlResponse'] = $xml;
            $_SESSION['user'] = (string) $xmlResponse->customer->lastname . ', ' . (string) $xmlResponse->customer->firstname;
            $_SESSION['secure'] = TRUE;
        }
        $response = new JsonResponse(
                array(
            'message' => 'success',
            'xml' => $_SESSION['user']), 200);
        return $response;
    }

    public function logoutAction() {
        session_destroy();
        $response = new JsonResponse(
                array(
            'message' => 'success',
            'xml' => ''), 200);
        return $response;
    }

    private function getXML($params) {
        $data = new Model($params['account'], $params['site_id'], $params['site_secure_code']);
        $credentials = $data->makeCredentials();
        $xmlResponse = $data->getXMLResponse($credentials);

        return $xmlResponse;
    }

    private function renderXML($iterator, $twig_file) {
        $params = array(
            'xml' => $iterator
        );

        $template = $this->renderView($twig_file, $params);
        $response = new JsonResponse(
                array('message' => 'success',
            'xml' => $template), 200
        );

        return $response;
    }

    private function getResponse(Request $request){
        if (!$request->isXmlHttpRequest()) {
            return new JsonResponse(array('message' => 'You can access this only using Ajax!'), 400);
        }
        if (!isset($_SESSION['secure'])) {
            $response = $this->renderXML('', 'default\logout.html.twig');
        } else {
            $xml = new \SimpleXMLElement($_SESSION['xmlResponse']);
            switch ($this->type) {
                case 'ewallet':
                $this->xml_out = $xml->ewallet[0];
                $this->twig_file_out = 'default\ewallet.html.twig';
                break;
            case 'transaction':
                $this->xml_out = $xml->transaction[0];
                $this->twig_file_out = 'default\ewallet.html.twig';
                break;
            case 'paymentdetails':
                $this->xml_out = $xml->paymentdetails[0];
                $this->twig_file_out = 'default\paymentdetails.html.twig';
                break;
            case 'customer':
                $this->xml_out = $xml->customer[0];
                $this->twig_file_out = 'default\customer.html.twig';
                break;
            case 'customer_delivery':
                $this->xml_out = $xml->{"customer-delivery"}[0];
                $this->twig_file_out = 'default\customer_delivery.html.twig';
                break;
            case 'shopping_cart':
                $this->xml_out = $xml->checkoutdata[0]->{"shopping-cart"}->items->item[0];
                $this->twig_file_out = 'default\shopping_cart.html.twig';
                break;
            }
            $response = $this->renderXML($this->xml_out, $this->twig_file_out);
        }
        return $response;
    }

    public function ewalletAction(Request $request) {
        $this->type = 'ewallet';
        return $this->getResponse($request);
    }

    public function transactionAction(Request $request) {
        $this->type = 'transaction';
        return $this->getResponse($request);
    }

    public function paymentAction(Request $request) {
        $this->type = 'paymentdetails';
        return $this->getResponse($request);
    }

    public function customerAction(Request $request) {
        $this->type = 'customer';
        return $this->getResponse($request);
    }

    public function customerDeliveryAction(Request $request) {
        $this->type = 'customer_delivery';
        return $this->getResponse($request);
    }

    public function shoppingCartAction(Request $request) {
        $this->type = 'shopping_cart';
        return $this->getResponse($request);
    }

}

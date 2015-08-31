<?php

/**
 * Controller for Salesforce helper actions.
 *
 * @license http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Salesforce\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormInterface;

use skyflow\Controller\AbstractHelperController;
use skyflow\Facade;

/**
 * Controller for Salesforce helper actions.
 */
class SalesforceHelperController extends AbstractHelperController
{
    /**
     * The query form.
     *
     * @var FormInterface
     */
    private $queryForm;

    /**
     * AbstractHelperController constructor.
     *
     * @param Request               $request      The HTTP request.
     * @param Facade                $addon        The addon facade.
     * @param FormInterface         $queryForm    The query form.
     */
    public function __construct(
        Request $request,
        Facade $addon,
        FormInterface $queryForm
    ) {
        parent::__construct($request, $addon);
        $this->queryForm = $queryForm;
    }

    /**
     * Get the SOQL query form.
     *
     * @return FormInterface The SOQL query form.
     */
    protected function getQueryForm()
    {
        return $this->queryForm;
    }

    /**
     * Send a SOQL Query to Salesforce.
     *
     * @return mixed
     */
    public function queryAction()
    {
        $this->getQueryForm()->handleRequest($this->getRequest());

        if ($this->getQueryForm()->isSubmitted() && $this->getQueryForm()->isValid()) {
            $array = $this->getQueryForm()->getData();
            $query = $array['Request'];

            $data = $this->getAddon()->getService('data')->soql($query);

            return $this->getTwig()->render(
                'results.html.twig',
                array(
                    'results' => $data,
                )
            );
        }

        return $this->getTwig()->render(
            'salesforce-apihelper.html.twig',
            array(
                'requestForm' => $this->getQueryForm()->createView(),
            )
        );
    }
}

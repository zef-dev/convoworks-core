<?php


namespace Convo\Core\Adapters\Google\Common\Intent;


class GoogleActionsIntentResolver
{
    public function resolveIntent($intent, $type = '', $valueObject = array()) {
        switch ($intent) {
            case IActionsIntent::MAIN:
                return $this->_getMainIntent();
            case IActionsIntent::TEXT:
                return $this->_getTextIntent();
            case IActionsIntent::CONFIRMATION:
                return $this->_getConfirmationHelperIntent($intent, $type, $valueObject);
            case IActionsIntent::OPTION:
                return $this->_getOptionHelperIntent($intent, $type, $valueObject);
            case IActionsIntent::SIGN_IN:
                return $this->_getSignInIntent($intent, $type, $valueObject);
            default:
                return $this->_getCustomIntent($intent);
        }
    }

    private function _getTextIntent() {
        return array(
            array("intent" => IActionsIntent::TEXT)
        );
    }

    private function _getMainIntent() {
        return array(
            array("intent" => IActionsIntent::MAIN)
        );
    }

    private function _getCustomIntent($intent) {
        return array(
            array("intent" => $intent)
        );
    }

    private function _getConfirmationHelperIntent($intent, $type, $valueObject) {
        return array(
            array(
                "intent" => $intent,
                "inputValueData" => array(
                    "@type" => $type,
                    "dialogSpec" => $valueObject
                )
            )
        );
    }

    private function _getOptionHelperIntent($intent, $type, $valueObject) {
        return array(
            array(
                "intent" => $intent,
                "inputValueData" => array(
                    "@type" => $type,
                    "carouselSelect" => $valueObject
                )
            )
        );
    }

    private function _getSignInIntent($intent, $type, $valueObject) {
        return array(
            array(
                "intent" => $intent,
                "inputValueData" => array(
                    "@type" => $type,
                    "optContext" => $valueObject
                )
            )
        );
    }

    public function __toString()
    {
        return get_class($this);
    }
}

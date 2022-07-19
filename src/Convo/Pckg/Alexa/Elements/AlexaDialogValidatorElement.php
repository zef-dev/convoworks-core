<?php

namespace Convo\Pckg\Alexa\Elements;

class AlexaDialogValidatorElement extends \Convo\Core\Workflow\AbstractWorkflowComponent
{
    private $_slotToValidate = '';
    private $_alexaPrompts = [];
    private $_validationRule = '';

    // isInSet
    private $_validationRuleIsInSet;

    // isNotInSet
    private $_validationRuleIsNotInSet;

    // isGreaterThan
    private $_validationRuleIsGreaterThan;

    // isGreaterThanOrEqualTo
    private $_validationRuleIsGreaterThanOrEqualTo;

    // isLessThan
    private $_validationRuleIsLessThan;

    // isLessThanOrEqualTo
    private $_validationRuleIsLessThanOrEqualTo;

    // isInDuration
    private $_validationRuleIsInDurationStart;
    private $_validationRuleIsInDurationEnd;

    // isNotInDuration
    private $_validationRuleIsNotInDurationStart;
    private $_validationRuleIsNotInDurationEnd;

    public function __construct( $properties) {
        parent::__construct( $properties);

        $this->_validationRule = $properties['validation_rule'];

        $this->_validationRuleIsInSet = $properties['validation_rule_is_in_set'] ?? [];
        $this->_validationRuleIsNotInSet = $properties['validation_rule_is_not_in_set'] ?? [];
        $this->_validationRuleIsGreaterThan = $properties['validation_rule_is_greater_than'] ?? '';
        $this->_validationRuleIsGreaterThanOrEqualTo = $properties['validation_rule_is_greater_than_or_equal_to'] ?? '';
        $this->_validationRuleIsLessThan = $properties['validation_rule_is_less_than'] ?? '';
        $this->_validationRuleIsLessThanOrEqualTo = $properties['validation_rule_is_less_than_or_equal_to'] ?? '';
        $this->_validationRuleIsInDurationStart = $properties['validation_rule_is_in_duration_start'] ?? '';
        $this->_validationRuleIsInDurationEnd = $properties['validation_rule_is_in_duration_end'] ?? '';
        $this->_validationRuleIsNotInDurationStart = $properties['validation_rule_is_not_in_duration_start'] ?? '';
        $this->_validationRuleIsNotInDurationEnd = $properties['validation_rule_is_not_in_duration_end'] ?? '';

        $this->_alexaPrompts = $properties['alexa_prompts'] ?? [];
    }

    public function setSlotToValidate($slotToValidate) {
        $this->_slotToValidate = $slotToValidate;
    }

    public function getSlotToValidate() {
        return $this->_slotToValidate;
    }

    public function getDialogValidation() {
        $validationRule = $this->getService()->evaluateString($this->_validationRule);
        $validation = [
            'slotToValidate' => $this->getSlotToValidate(),
            'prompts' => $this->_getAlexaValidationPrompts()
        ];
        $validation['validation']['name'] = $validationRule;
        $validation['validation']['properties'] = $this->_getValidationProperties($validationRule);

        return $validation;
    }

    private function _getAlexaValidationPrompts() {
        $alexaPrompts = [];
        foreach ($this->_alexaPrompts as $alexaPrompt) {
            $alexaPrompts[] = $alexaPrompt->getAlexaPrompt();
        }
        return $alexaPrompts;
    }

    private function _getValidationProperties($validationRule) {
        switch ($validationRule) {
            case 'hasEntityResolutionMatch':
                return [];
            case 'isInSet':
                return ["values" => $this->getService()->evaluateString($this->_validationRuleIsInSet)];
            case 'isNotInSet':
                return ["values" => $this->getService()->evaluateString($this->_validationRuleIsNotInSet)];
            case 'isGreaterThan':
                return ["value" => $this->getService()->evaluateString($this->_validationRuleIsGreaterThan)];
            case 'isGreaterThanOrEqualTo':
                return ["value" => $this->getService()->evaluateString($this->_validationRuleIsGreaterThanOrEqualTo)];
            case 'isLessThan':
                return ["value" => $this->getService()->evaluateString($this->_validationRuleIsLessThan)];
            case 'isLessThanOrEqualTo':
                return ["value" => $this->getService()->evaluateString($this->_validationRuleIsLessThanOrEqualTo)];
            case 'isInDuration':
                return [
                    "start" => $this->getService()->evaluateString($this->_validationRuleIsInDurationStart),
                    "end" => $this->getService()->evaluateString($this->_validationRuleIsInDurationEnd)
                ];
            case 'isNotInDuration':
                return [
                    "start" => $this->getService()->evaluateString($this->_validationRuleIsNotInDurationStart),
                    "end" => $this->getService()->evaluateString($this->_validationRuleIsNotInDurationEnd)
                ];
            default:
                throw new \Exception('Unsupported validation rule ['.$validationRule.']');
        }
    }
}

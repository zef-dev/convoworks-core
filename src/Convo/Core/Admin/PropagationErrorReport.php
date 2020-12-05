<?php


namespace Convo\Core\Admin;


class PropagationErrorReport
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    public function __construct($logger)
    {
        $this->_logger = $logger;
    }

    public function craftErrorReport($error, $platformId) {
        $errorMessage = json_decode($error, true);
        $errorReport = [];
        $errorReport["details"] = "Not Available";

        if (json_last_error()) {
            $errorReport["message"] = $error;
            return json_encode($errorReport);
        }

        $this->_logger->info($platformId . " error: " . $error);
        $errorReport["message"] = isset($errorMessage["message"]) ? $errorMessage["message"] : "Unexpected error in propagation.";
        switch ($platformId) {
            case 'amazon':
                $errorReport["details"] = isset($errorMessage["violations"]) ? $errorMessage["violations"] : [];
                $errorReport["details"] = $this->_extractAmazonErrorDetails($errorReport["details"], "message");
                break;
            case 'dialogflow':
                $errorReport["details"] = isset($errorMessage["details"]) ? $errorMessage["details"] : [];
                break;
            default:
                break;
        }

        return json_encode($errorReport);
    }

    private function _extractAmazonErrorDetails($errorReportDetails, $messageKey) {
        $errorDetails = [];
        foreach ($errorReportDetails as $errorReportDetail) {
            array_push($errorDetails, $errorReportDetail[$messageKey]);
        }
        return implode(";", $errorDetails);
    }
}

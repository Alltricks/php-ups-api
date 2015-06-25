<?php
namespace Ups;

use DOMDocument;
use SimpleXMLElement;
use Exception;
use stdClass;

use Ups\Entity\Address;

/**
 * Address Validation API Wrapper
 *
 * @package ups
 */
class Locator extends Ups
{
    const ENDPOINT = '/Locator';

    /**
     * Request Options
     */
    const REQUEST_OPTION_LOCATIONS = 1;
    const REQUEST_OPTION_ADDITIONAL_SERVICES = 8;
    const REQUEST_OPTION_PROGRAM_TYPE = 16;
    const REQUEST_OPTION_ADDITIONAL_SERVICES_AND_PROGRAM_TYPE = 24;
    const REQUEST_OPTION_RETAIL_LOCATIONS = 32;
    const REQUEST_OPTION_RETAIL_LOCATIONS_AND_ADDITIONAL_SERVICES = 40;
    const REQUEST_OPTION_RETAIL_LOCATIONS_AND_PROGRAM_TYPE = 48;
    const REQUEST_OPTION_RETAIL_LOCATIONS_AND_ADDITIONAL_SERVICES_AND_PROGRAM_TYPE = 56;
    const REQUEST_OPTION_UPS_ACCESS_POINT = 64;

    /**
     * Get address suggestions from UPS
     *
     * @param $address
     * @param int $range
     * @param int $maxSuggestion
     * @return stdClass
     * @throws Exception
     */
    public function search(Address $address, $requestOption = self::REQUEST_OPTION_UPS_ACCESS_POINT, $searchRadius = 5, $maxSuggestion = 15)
    {
        $access = $this->createAccess();
        $request = $this->createRequest($address, $requestOption, $searchRadius, $maxSuggestion);

        $this->response = $this->getRequest()->request($access, $request, $this->compileEndpointUrl(self::ENDPOINT));
        $response = $this->response->getResponse();

        if (null === $response) {
            throw new Exception("Failure (0): Unknown error", 0);
        }

        if ($response instanceof SimpleXMLElement && $response->Response->ResponseStatusCode == 0) {
            throw new Exception(
                "Failure ({$response->Response->Error->ErrorSeverity}): {$response->Response->Error->ErrorDescription}",
                (int)$response->Response->Error->ErrorCode
            );
        }

        return $this->formatResponse($response);
    }

    /**
     * Create the XAV request
     *
     * @return string
     */
    private function createRequest(Address $address, $requestOption, $searchRadius, $maxSuggestion)
    {
        $xml = new DOMDocument();
        $xml->formatOutput = true;

        $avRequest = $xml->appendChild($xml->createElement("LocatorRequest"));
        $avRequest->setAttribute('xml:lang', 'en-US');

        $request = $avRequest->appendChild($xml->createElement("Request"));

        $node = $xml->importNode($this->createTransactionNode(), true);

        $request->appendChild($node);
        $request->appendChild($xml->createElement("ToolVersion", 1));
        $request->appendChild($xml->createElement("RequestAction", "Locator"));
        $request->appendChild($xml->createElement("RequestOption", $requestOption));

        $avRequest->appendChild($xml->createElement("MaximumListSize", $maxSuggestion));

        $addressNode = $avRequest->appendChild($xml->createElement("OriginAddress"));
        $addressNode->appendChild($xml->createElement("ConsigneeName", $address->getAttentionName()));
        $addressNode->appendChild($xml->createElement("BuildingName", $address->getBuildingName()));
        $addressNode->appendChild($xml->createElement("AddressLine", $address->getAddressLine1()));
        $addressNode->appendChild($xml->createElement("AddressLine", $address->getAddressLine2()));
        $addressNode->appendChild($xml->createElement("AddressLine", $address->getAddressLine3()));
        $addressNode->appendChild($xml->createElement("PoliticalDivision2", $address->getStateProvinceCode()));
        $addressNode->appendChild($xml->createElement("PoliticalDivision1", $address->getCity()));
        $addressNode->appendChild($xml->createElement("CountryCode", $address->getCountryCode()));
        $addressNode->appendChild($xml->createElement("PostcodePrimaryLow", $address->getPostalCode()));

        return $xml->saveXML();
    }

    /**
     * Format the response
     *
     * @param SimpleXMLElement $response
     * @return stdClass
     */
    private function formatResponse(SimpleXMLElement $response)
    {
        return $this->convertXmlObject($response->AddressKeyFormat);
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param ResponseInterface $response
     * @return $this
     */
    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;

        return $this;
    }
}
<?php
namespace Ups;

use DOMDocument;
use SimpleXMLElement;
use Exception;
use stdClass;

use Ups\Entity\Address;
use Ups\Entity\Radius;

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
     * @param Address $address
     * @param Radius $range
     * @param int $maxSuggestion
     * @return stdClass
     * @throws Exception
     */
    public function search(Address $address, Radius $radius, $requestOption = self::REQUEST_OPTION_LOCATIONS, $maxSuggestion = 15, $languageCode = 'eng', $local = 'en-US')
    {
        $access = $this->createAccess();
        $request = $this->createRequest($address, $radius, $requestOption, $maxSuggestion, $languageCode, $local);

        $this->response = (new Request)->request($access, $request, $this->compileEndpointUrl(self::ENDPOINT));
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
    private function createRequest(Address $address, Radius $radius, $requestOption, $maxSuggestion, $languageCode, $local)
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


        $avTranslate = $avRequest->appendChild($xml->createElement("Translate", "Translate"));
        $avTranslate->appendChild($xml->createElement("LanguageCode", $languageCode));
        $avTranslate->appendChild($xml->createElement("Local", $local));

        $avSearch = $avRequest->appendChild($xml->createElement("LocationSearchCriteria"));
        $avSearch->appendChild($xml->createElement("MaximumListSize", $maxSuggestion));
        $avSearch->appendChild($xml->createElement("SearchRadius", $radius->getRadius()));

        $origineAddressNode = $avRequest->appendChild($xml->createElement("OriginAddress"));

        $keyAddressNode = $origineAddressNode->appendChild($xml->createElement("AddressKeyFormat"));
        $keyAddressNode->appendChild($xml->createElement("BuildingName", $address->getBuildingName()));
        $keyAddressNode->appendChild($xml->createElement("AddressLine", $address->getAddressLine1()));
        $keyAddressNode->appendChild($xml->createElement("AddressLine2", $address->getAddressLine2()));
        $keyAddressNode->appendChild($xml->createElement("AddressLine3", $address->getAddressLine3()));
        $keyAddressNode->appendChild($xml->createElement("PoliticalDivision2", $address->getPoliticalDivision2()));
        $keyAddressNode->appendChild($xml->createElement("PoliticalDivision1", $address->getPoliticalDivision1()));
        $keyAddressNode->appendChild($xml->createElement("CountryCode", $address->getCountryCode()));
        $keyAddressNode->appendChild($xml->createElement("PostcodePrimaryLow", $address->getPostalCode()));
        $keyAddressNode->appendChild($xml->createElement("MaximumListSize", $maxSuggestion));

        $unit = $avRequest->appendChild($xml->createElement("UnitOfMeasurement"));

        $unit->appendChild($xml->createElement("Code", $radius->getUnit()));


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
        return $this->convertXmlObject($response->SearchResults);
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

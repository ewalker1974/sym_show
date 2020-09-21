<?php
/**
 * @author Yurii lunhol lunhol.yurii@gmail.com
 */

namespace App\PssWorkerBundle\Service;

use FedEx\TrackService\ComplexType;
use FedEx\TrackService\SimpleType;
use Psr\Container\ContainerInterface;

/**
 * Class FedexService
 *
 * @package App\PssWorkerBundle\Service
 */
class FedexService
{
    const FEDEX_TRACK_SERVICE_ID = 'trck';

    protected $productionMode;
    protected $fedexAccountNumber;
    protected $fedexMeterNumber;
    protected $fedexKey;
    protected $fedexPassword;

    /**
     * FedexService constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->productionMode = $container->getParameter('fedex_production_mode');

        $this->fedexAccountNumber = $container->getParameter('fedex_account_number');
        $this->fedexMeterNumber = $container->getParameter('fedex_meter_number');
        $this->fedexKey = $container->getParameter('fedex_key');
        $this->fedexPassword = $container->getParameter('fedex_password');
    }

    /**
     * @return bool
     */
    public function isProductionMode() : bool
    {
        return (bool) $this->productionMode;
    }

    /**
     * @return \FedEx\TrackService\ComplexType\TrackRequest
     */
    public function getTrackRequest()
    {
        $webAuthenticationDetail = $this->getWebAuthenticationDetail();
        $clientDetail = $this->getClientDetail();
        $versionId = $this->getTrackServiceVersionId();

        $request = new ComplexType\TrackRequest();

        $request
            ->setWebAuthenticationDetail($webAuthenticationDetail)
            ->setClientDetail($clientDetail)
            ->setVersion($versionId);

        return $request;
    }

    /**
     * @return \FedEx\TrackService\ComplexType\WebAuthenticationCredential
     */
    protected function getUserCredential()
    {
        $userCredential = new ComplexType\WebAuthenticationCredential();
        $userCredential
            ->setKey($this->fedexKey)
            ->setPassword($this->fedexPassword);

        return $userCredential;
    }

    /**
     * @return \FedEx\TrackService\ComplexType\WebAuthenticationDetail
     */
    protected function getWebAuthenticationDetail()
    {
        $userCredential = $this->getUserCredential();

        $webAuthenticationDetail = new ComplexType\WebAuthenticationDetail();
        $webAuthenticationDetail->setUserCredential($userCredential);

        return $webAuthenticationDetail;
    }

    /**
     * @return \FedEx\TrackService\ComplexType\ClientDetail
     */
    protected function getClientDetail()
    {
        $clientDetail = new ComplexType\ClientDetail();
        $clientDetail
            ->setAccountNumber($this->fedexAccountNumber)
            ->setMeterNumber($this->fedexMeterNumber);

        return $clientDetail;
    }

    /**
     * @return \FedEx\TrackService\ComplexType\VersionId
     */
    protected function getTrackServiceVersionId()
    {
        $version = new ComplexType\VersionId();
        $version
            ->setMajor(5)
            ->setIntermediate(0)
            ->setMinor(0)
            ->setServiceId(self::FEDEX_TRACK_SERVICE_ID);

        return $version;
    }

    /**
     * @param string|int $trackingId
     * @param string $identifierType
     * @return \FedEx\TrackService\ComplexType\TrackPackageIdentifier
     */
    public function getTrackPackageIdentifier(
        $trackingId,
        $identifierType = SimpleType\TrackIdentifierType::_TRACKING_NUMBER_OR_DOORTAG
    ) {
        $identifier = new ComplexType\TrackPackageIdentifier();
        $identifier
            ->setType($identifierType)
            ->setValue($trackingId);

        return $identifier;
    }
}

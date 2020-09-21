<?php

namespace App\PssWorkerBundle\DataObject\Kestrel;

use App\PssWorkerBundle\DataObject\ISource;
use App\Entity\Shipment;
use App\Entity\Partner;
use App\PssWorkerBundle\DataObject\ShipmentData;
use App\PssWorkerBundle\SourceException;
use SecIT\ImapBundle\Service\Imap;
use App\PssWorkerBundle\DataObject\ISyncService;
use Psr\Log\LoggerInterface;

class KestrelSource implements ISource
{
    CONST MAX_MAIL_PROCESS = 100;
    /**
     * @var \PhpImap\Mailbox
     */
    private $dataSrc;
    private $from;
    private $sync;
    private $syncDate;
    protected $logger;


    public function __construct(Imap $imap, ISyncService $sync, string $connection, string $from, LoggerInterface $logger)
    {
        $this->dataSrc = $imap->get($connection);
        $this->from = $from;
        $this->sync = $sync;
        $this->logger = $logger;
    }

    private function preProcessField($fieldName, $fieldVal)
    {
        $result = trim($fieldVal);
        switch ($fieldName) {
            case 'statusDate': $result = new \DateTime(str_replace('/','-',$result));
            break;
        }
        return $result;
    }
    private function extractField($data, $fieldName, $fieldNr, $message)
    {
        $fieldVal = null;
        if (array_key_exists($fieldNr, $message)) {
            preg_match('/(?<=\: ).*/',$message[$fieldNr], $matches);
            if (count($matches) > 0 ) {
                $fieldVal = $this->preProcessField($fieldName,$matches[0]);
            }
        }
        $data->setField($fieldName, $fieldVal);
        return $data;
    }

    private function getShipment(string $mailBody, $date): ?ShipmentData
    {
        $statuses = [
            'BOOKED' => Shipment::SHIPMENT_BOOKED_STATUS,
            'COLLECTED' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
            'PACKED' => Shipment::SHIPMENT_PLANNED_STATUS,
            'DEPARTED' => Shipment::SHIPMENT_IN_TRANSIT_STATUS,
            'ETA' => Shipment::SHIPMENT_PLANNED_STATUS,
            'ARRIVED AT PACKERS' => Shipment::SHIPMENT_PLANNED_STATUS,
            'DELIVERED' => Shipment::SHIPMENT_DELIVERED_STATUS,
            ];

        $fieldLists = [
            'shipmentNumber',
            'deliveryStatus',
            'statusDate',
            'trackingLink',
        ];
        $data  = explode("\n", $mailBody);

        $shipment  = new ShipmentData();

        foreach ($fieldLists as $fieldNr => $fieldName) {
            $this->extractField($shipment, $fieldName, $fieldNr, $data);
        }

        $orderNr = $shipment->getField('shipmentNumber');
        $shipment->setField('orderNumber', substr($orderNr,0,10));
        $status = $shipment->getField('deliveryStatus');

        if ($status === 'ETA') {
            $shipment->setField('eta', $shipment->getField('statusDate'));
        }
        if (array_key_exists($status, $statuses)) {
            $shipment->setField('pssStatus', $statuses[$status]);
        } else {
            throw new SourceException('Unknown status of order:'.$orderNr.' date:'.$date);
        }

        $date = new \DateTime('now');
        $shipment->setField('updatedAt', $date);
        $shipment->setField('partnerTag', Partner::KESTREL_PARTNER);

        return $shipment;
    }
    public function get(): ?\Traversable
    {

        try {
            $date = $this->sync->getLastSyncTimestamp('kestrel');
            $this->syncDate = $date;
            $mails = $this->dataSrc->searchMailbox('SINCE '.$date->format('Y-m-d') .' FROM '.$this->from);
            $numMails = 0;
        } catch (\Throwable $e) {
            throw new SourceException($e->getMessage());
        }
        foreach ($mails as $mail) {
            try {
                $mailData =  $this->dataSrc->getMail($mail);
                $dateMail = new \DateTime($mailData->date);
                if ($dateMail > $date) {
                    if ($this->syncDate < $dateMail) {
                        $this->syncDate = $dateMail;
                    }
                    $data = $mailData->textPlain;
                    $orderData = $this->getShipment($data, $mailData->date);
                    if ($orderData) {
                        yield $orderData;
                    }
                    $numMails++;
                    if ($numMails > self::MAX_MAIL_PROCESS) {
                        break;
                    }
                }
            } catch (\Throwable $e) {
                $this->logger->error(
                    $e->getMessage().' mail of:  '.$mailData->date. ' content: '.$mailData->textPlain
                );
            }

        }


    }
    public function setConstraint(string $name, $value):ISource
    {
        return $this;
    }

    public function setSyncTime(): void
    {
        $this->sync->setSyncTimestamp('kestrel', $this->syncDate);
    }
}

<?php

namespace App\EventListener;

use App\Entity\MessageView;
use App\Entity\Shipment;
use App\Service\NewMessageViewCache\MessageViewStore;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\LifecycleEventArgs;

class MessageViewCreateListener
{
    const NULL_PARTNER_ID = 'p';
    /**
     * @var MessageViewStore
     */
    private $store;

    /**
     * @var array
     */
    private $addedMessageViews = [];

    public function __construct(MessageViewStore $store)
    {
        $this->store = $store;
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        /**
         * @var MessageView
         */
        $entity = $args->getEntity();

        if ($entity instanceof MessageView) {
            $partner = $entity->getPartner();
            if ($partner) {
                $partnerId = $partner->getId();
            } else {
                $partnerId = self::NULL_PARTNER_ID;
            }
            if (isset($this->addedMessageViews[$partnerId])) {
                $this->addedMessageViews[$partnerId]['ids'][] = $entity->getMessage()->getId();
            } else {
                $this->addedMessageViews[$partnerId] = [
                    'partner' => $partner,
                    'ids' => [$entity->getMessage()->getId()]
                ];
            }
        }
    }

    public function postFlush(PostFlushEventArgs $args)
    {
        foreach ($this->addedMessageViews as $partner => $messageData)
        {
            $messages = $this->store->getNewMessageViews($messageData['partner']);
            $messages->addViewMessage($messageData['ids']);
            $this->store->setNewMessageViews($messageData['partner'], $messages);
        }

        $this->clearAddedMesssageViews();
    }

    private function clearAddedMesssageViews()
    {
        $this->addedMessageViews = [];
    }

}
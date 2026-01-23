<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité représentant une commande client
 */
#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
#[ORM\Index(columns: ['status'], name: 'idx_order_status')]
#[ORM\Index(columns: ['dateat'], name: 'idx_order_date')]
#[ORM\Index(columns: ['user_id', 'dateat'], name: 'idx_order_user_date')]
class Order
{
    /**
     * Identifiant unique de la commande
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Statut de la commande (ex: pending, paid, shipped, delivered)
     */
    #[ORM\Column(length: 32)]
    private ?string $status = null;

    /**
     * Montant total de la commande
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $total = null;

    /**
     * Date et heure à laquelle la commande a été passée
     */
    #[ORM\Column]
    private ?\DateTime $dateat = null;

    /**
     * Utilisateur ayant passé la commande
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * Identifiant de la session Stripe Checkout
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeSessionId = null;

    /**
     * Identifiant de paiement Stripe (payment intent)
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripePaymentIntentId = null;

    /**
     * Numéro de suivi du colis
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $trackingNumber = null;

    /**
     * Transporteur (ex: Colissimo, DHL, UPS)
     */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $carrier = null;

    /**
     * Date et heure d'expédition
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $shippedAt = null;

    #[ORM\OneToMany(mappedBy: 'orderRef', targetEntity: OrderItem::class, orphanRemoval: true)]
    private $orderItems;

    public function __construct()
    {
        $this->orderItems = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Récupère le statut actuel de la commande
     * 
     * @return string|null Le statut de la commande
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * Définit le statut de la commande
     * 
     * @param string $status Le nouveau statut
     * @return static
     */
    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Récupère le montant total de la commande
     * 
     * @return string|null Le montant total
     */
    public function getTotal(): ?string
    {
        return $this->total;
    }

    /**
     * Définit le montant total de la commande
     * 
     * @param string $total Le montant total
     * @return static
     */
    public function setTotal(string $total): static
    {
        $this->total = $total;

        return $this;
    }

    /**
     * Récupère la date de création de la commande
     * 
     * @return \DateTime|null La date de la commande
     */
    public function getDateat(): ?\DateTime
    {
        return $this->dateat;
    }

    /**
     * Définit la date de création de la commande
     * 
     * @param \DateTime $dateat La nouvelle date
     * @return static
     */
    public function setDateat(\DateTime $dateat): static
    {
        $this->dateat = $dateat;

        return $this;
    }

    /**
     * Récupère l'utilisateur associé à la commande
     * 
     * @return User|null L'entité User
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Définit l'utilisateur associé à la commande
     * 
     * @param User|null $user L'entité User
     * @return static
     */
    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection<int, OrderItem>
     */
    public function getOrderItems(): \Doctrine\Common\Collections\Collection
    {
        return $this->orderItems;
    }

    public function addOrderItem(OrderItem $orderItem): static
    {
        if (!$this->orderItems->contains($orderItem)) {
            $this->orderItems->add($orderItem);
            $orderItem->setOrderRef($this);
        }

        return $this;
    }

    public function removeOrderItem(OrderItem $orderItem): static
    {
        if ($this->orderItems->removeElement($orderItem)) {
            // set the owning side to null (unless already changed)
            if ($orderItem->getOrderRef() === $this) {
                $orderItem->setOrderRef(null);
            }
        }

        return $this;
    }

    /**
     * Récupère l'identifiant de session Stripe Checkout
     */
    public function getStripeSessionId(): ?string
    {
        return $this->stripeSessionId;
    }

    /**
     * Définit l'identifiant de session Stripe Checkout
     */
    public function setStripeSessionId(?string $stripeSessionId): static
    {
        $this->stripeSessionId = $stripeSessionId;

        return $this;
    }

    /**
     * Récupère l'identifiant de paiement Stripe
     */
    public function getStripePaymentIntentId(): ?string
    {
        return $this->stripePaymentIntentId;
    }

    /**
     * Définit l'identifiant de paiement Stripe
     */
    public function setStripePaymentIntentId(?string $stripePaymentIntentId): static
    {
        $this->stripePaymentIntentId = $stripePaymentIntentId;

        return $this;
    }

    /**
     * Récupère le numéro de suivi
     */
    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    /**
     * Définit le numéro de suivi
     */
    public function setTrackingNumber(?string $trackingNumber): static
    {
        $this->trackingNumber = $trackingNumber;

        return $this;
    }

    /**
     * Récupère le transporteur
     */
    public function getCarrier(): ?string
    {
        return $this->carrier;
    }

    /**
     * Définit le transporteur
     */
    public function setCarrier(?string $carrier): static
    {
        $this->carrier = $carrier;

        return $this;
    }

    /**
     * Récupère la date d'expédition
     */
    public function getShippedAt(): ?\DateTimeInterface
    {
        return $this->shippedAt;
    }

    /**
     * Définit la date d'expédition
     */
    public function setShippedAt(?\DateTimeInterface $shippedAt): static
    {
        $this->shippedAt = $shippedAt;

        return $this;
    }
}

<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Entité représentant un utilisateur/client du site
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * Identifiant unique de l'utilisateur
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Adresse email de l'utilisateur (sert d'identifiant de connexion)
     */
    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> Liste des rôles de l'utilisateur (ex: ROLE_USER, ROLE_ADMIN)
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string Le mot de passe haché
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * Prénom de l'utilisateur
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstName = null;

    /**
     * Nom de famille de l'utilisateur
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastName = null;

    /**
     * Numéro de téléphone
     */
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    /**
     * Adresse postale complète
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    /**
     * Code postal
     */
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $postalCode = null;

    /**
     * Ville
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $city = null;

    /**
     * Pays
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $country = null;

    /**
     * Token de réinitialisation du mot de passe
     */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $resetToken = null;

    /**
     * Date d'expiration du token de réinitialisation
     */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $resetTokenExpiresAt = null;

    /**
     * Contenu du panier persistant (stocké en base de données)
     */
    #[ORM\Column(nullable: true)]
    private ?array $cart = [];

    public function getCart(): ?array
    {
        return $this->cart;
    }

    public function setCart(?array $cart): static
    {
        $this->cart = $cart;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): static
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Indique si le compte est actif ou désactivé
     */
    #[ORM\Column(options: ["default" => true])]
    private bool $isActive = true;

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): static
    {
        $this->resetToken = $resetToken;

        return $this;
    }

    public function getResetTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->resetTokenExpiresAt;
    }

    public function setResetTokenExpiresAt(?\DateTimeImmutable $resetTokenExpiresAt): static
    {
        $this->resetTokenExpiresAt = $resetTokenExpiresAt;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }
}

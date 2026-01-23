<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité représentant un produit de la boutique
 */
#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Index(columns: ['category'], name: 'idx_product_category')]
#[ORM\Index(columns: ['is_featured'], name: 'idx_product_featured')]
#[ORM\Index(columns: ['price'], name: 'idx_product_price')]
#[ORM\Index(columns: ['name'], name: 'idx_product_name')]
#[ORM\Index(columns: ['category', 'is_featured'], name: 'idx_product_category_featured')]
class Product
{
    /**
     * Identifiant unique du produit
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Nom du produit
     */
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * Description détaillée du produit
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    /**
     * Prix unitaire du produit
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $price = null;

    /**
     * Quantité disponible en stock
     */
    #[ORM\Column(nullable: true)]
    private ?int $stock = null;

    /**
     * Catégorie du produit (simple chaîne de caractères pour le moment)
     */
    #[ORM\Column(length: 255)]
    private ?string $category = null;

    /**
     * Nom du fichier image associé au produit
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageName = null;

    /**
     * Indique si le produit est mis en avant sur la page d'accueil
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private ?bool $isFeatured = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(?int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageName(?string $imageName): static
    {
        $this->imageName = $imageName;

        return $this;
    }

    public function isFeatured(): ?bool
    {
        return $this->isFeatured;
    }

    public function setIsFeatured(bool $isFeatured): static
    {
        $this->isFeatured = $isFeatured;

        return $this;
    }
}

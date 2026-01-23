<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité représentant une catégorie de produits
 */
#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\Index(columns: ['name'], name: 'idx_category_name')]
#[ORM\Index(columns: ['slug'], name: 'idx_category_slug')]
class Category
{
    /**
     * Identifiant unique de la catégorie
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Nom de la catégorie (ex: Électronique, Vêtements)
     */
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * Identifiant URL-friendly pour la catégorie
     */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $slug = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Récupère le nom de la catégorie
     * 
     * @return string|null Le nom de la catégorie
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Définit le nom de la catégorie
     * 
     * @param string $name Le nouveau nom
     * @return static
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Récupère l'identifiant URL-friendly (slug) de la catégorie
     * 
     * @return string|null Le slug de la catégorie
     */
    public function getSlug(): ?string
    {
        return $this->slug;
    }

    /**
     * Définit l'identifiant URL-friendly (slug) de la catégorie
     * 
     * @param string|null $slug Le nouveau slug
     * @return static
     */
    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }
}

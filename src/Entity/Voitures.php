<?php

namespace App\Entity;

use App\Repository\VoituresRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VoituresRepository::class)]
class Voitures
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $voiture = null;

    #[ORM\Column]
    private ?\DateTime $dateimat = null;

    #[ORM\Column(length: 255)]
    private ?string $fumeur = null;

    #[ORM\Column(length: 255)]
    private ?string $annimaux = null;

    #[ORM\Column(length: 255)]
    private ?string $marque = null;

    #[ORM\Column]
    private ?int $place = null;

    #[ORM\Column(length: 255)]
    private ?string $modele = null;

    #[ORM\Column(length: 255)]
    private ?string $couleur = null;

    #[ORM\Column(type: Types::BLOB, nullable: true)]
    private $image = null;

    /**
     * @var Collection<int, Trajets>
     */
    #[ORM\OneToMany(targetEntity: Trajets::class, mappedBy: 'voiture')]
    private Collection $trajets;

    /**
     * @var Collection<int, Trajetsencours>
     */
    #[ORM\OneToMany(targetEntity: Trajetsencours::class, mappedBy: 'voiture', orphanRemoval: true)]
    private Collection $trajetsencours;

    /**
     * @var Collection<int, Trajetsfini>
     */
    #[ORM\OneToMany(targetEntity: Trajetsfini::class, mappedBy: 'voiture', orphanRemoval: true)]
    private Collection $trajetsfinis;

    public function __construct()
    {
        $this->trajets = new ArrayCollection();
        $this->trajetsencours = new ArrayCollection();
        $this->trajetsfinis = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVoiture(): ?string
    {
        return $this->voiture;
    }

    public function setVoiture(string $voiture): static
    {
        $this->voiture = $voiture;

        return $this;
    }

    public function getDateimat(): ?\DateTime
    {
        return $this->dateimat;
    }

    public function setDateimat(\DateTime $dateimat): static
    {
        $this->dateimat = $dateimat;

        return $this;
    }

    public function getFumeur(): ?string
    {
        return $this->fumeur;
    }

    public function setFumeur(string $fumeur): static
    {
        $this->fumeur = $fumeur;

        return $this;
    }

    public function getAnnimaux(): ?string
    {
        return $this->annimaux;
    }

    public function setAnnimaux(string $annimaux): static
    {
        $this->annimaux = $annimaux;

        return $this;
    }

    public function getMarque(): ?string
    {
        return $this->marque;
    }

    public function setMarque(string $marque): static
    {
        $this->marque = $marque;

        return $this;
    }

    public function getPlace(): ?int
    {
        return $this->place;
    }

    public function setPlace(int $place): static
    {
        $this->place = $place;

        return $this;
    }

    public function getModele(): ?string
    {
        return $this->modele;
    }

    public function setModele(string $modele): static
    {
        $this->modele = $modele;

        return $this;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(string $couleur): static
    {
        $this->couleur = $couleur;

        return $this;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setImage($image): static
    {
        $this->image = $image;

        return $this;
    }

    /**
     * @return Collection<int, Trajets>
     */
    public function getTrajets(): Collection
    {
        return $this->trajets;
    }

    public function addTrajet(Trajets $trajet): static
    {
        if (!$this->trajets->contains($trajet)) {
            $this->trajets->add($trajet);
            $trajet->setVoiture($this);
        }

        return $this;
    }

    public function removeTrajet(Trajets $trajet): static
    {
        if ($this->trajets->removeElement($trajet)) {
            // set the owning side to null (unless already changed)
            if ($trajet->getVoiture() === $this) {
                $trajet->setVoiture(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Trajetsencours>
     */
    public function getTrajetsencours(): Collection
    {
        return $this->trajetsencours;
    }

    public function addTrajetsencour(Trajetsencours $trajetsencour): static
    {
        if (!$this->trajetsencours->contains($trajetsencour)) {
            $this->trajetsencours->add($trajetsencour);
            $trajetsencour->setVoiture($this);
        }

        return $this;
    }

    public function removeTrajetsencour(Trajetsencours $trajetsencour): static
    {
        if ($this->trajetsencours->removeElement($trajetsencour)) {
            // set the owning side to null (unless already changed)
            if ($trajetsencour->getVoiture() === $this) {
                $trajetsencour->setVoiture(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Trajetsfini>
     */
    public function getTrajetsfinis(): Collection
    {
        return $this->trajetsfinis;
    }

    public function addTrajetsfini(Trajetsfini $trajetsfini): static
    {
        if (!$this->trajetsfinis->contains($trajetsfini)) {
            $this->trajetsfinis->add($trajetsfini);
            $trajetsfini->setVoiture($this);
        }

        return $this;
    }

    public function removeTrajetsfini(Trajetsfini $trajetsfini): static
    {
        if ($this->trajetsfinis->removeElement($trajetsfini)) {
            // set the owning side to null (unless already changed)
            if ($trajetsfini->getVoiture() === $this) {
                $trajetsfini->setVoiture(null);
            }
        }

        return $this;
    }
}

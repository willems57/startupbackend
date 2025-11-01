<?php

namespace App\Entity;

use App\Repository\TrajetsfiniRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrajetsfiniRepository::class)]
class Trajetsfini
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'trajetsfinis')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $conducteur = null;

    #[ORM\Column(length: 255)]
    private ?string $depart = null;

    #[ORM\Column(length: 255)]
    private ?string $arrive = null;

    #[ORM\Column]
    private ?\DateTime $date = null;

    #[ORM\Column]
    private ?int $duree = null;

    #[ORM\ManyToOne(inversedBy: 'trajetsfinis')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Voitures $voiture = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'trajetsfinis')]
    private Collection $passager;

    /**
     * @var Collection<int, Avis>
     */
    #[ORM\OneToMany(targetEntity: Avis::class, mappedBy: 'conducteur')]
    private Collection $avis;

    /**
     * @var Collection<int, Avisvalidation>
     */
    #[ORM\OneToMany(targetEntity: Avisvalidation::class, mappedBy: 'conducteur')]
    private Collection $avisvalidations;

    public function __construct()
    {
        $this->passager = new ArrayCollection();
        $this->avis = new ArrayCollection();
        $this->avisvalidations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConducteur(): ?User
    {
        return $this->conducteur;
    }

    public function setConducteur(?User $conducteur): static
    {
        $this->conducteur = $conducteur;

        return $this;
    }

    public function getDepart(): ?string
    {
        return $this->depart;
    }

    public function setDepart(string $depart): static
    {
        $this->depart = $depart;

        return $this;
    }

    public function getArrive(): ?string
    {
        return $this->arrive;
    }

    public function setArrive(string $arrive): static
    {
        $this->arrive = $arrive;

        return $this;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getDuree(): ?int
    {
        return $this->duree;
    }

    public function setDuree(int $duree): static
    {
        $this->duree = $duree;

        return $this;
    }

    public function getVoiture(): ?Voitures
    {
        return $this->voiture;
    }

    public function setVoiture(?Voitures $voiture): static
    {
        $this->voiture = $voiture;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getPassager(): Collection
    {
        return $this->passager;
    }

    public function addPassager(User $passager): static
    {
        if (!$this->passager->contains($passager)) {
            $this->passager->add($passager);
        }

        return $this;
    }

    public function removePassager(User $passager): static
    {
        $this->passager->removeElement($passager);

        return $this;
    }

    /**
     * @return Collection<int, Avis>
     */
    public function getAvis(): Collection
    {
        return $this->avis;
    }

    public function addAvi(Avis $avi): static
    {
        if (!$this->avis->contains($avi)) {
            $this->avis->add($avi);
            $avi->setConducteur($this);
        }

        return $this;
    }

    public function removeAvi(Avis $avi): static
    {
        if ($this->avis->removeElement($avi)) {
            // set the owning side to null (unless already changed)
            if ($avi->getConducteur() === $this) {
                $avi->setConducteur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Avisvalidation>
     */
    public function getAvisvalidations(): Collection
    {
        return $this->avisvalidations;
    }

    public function addAvisvalidation(Avisvalidation $avisvalidation): static
    {
        if (!$this->avisvalidations->contains($avisvalidation)) {
            $this->avisvalidations->add($avisvalidation);
            $avisvalidation->setConducteur($this);
        }

        return $this;
    }

    public function removeAvisvalidation(Avisvalidation $avisvalidation): static
    {
        if ($this->avisvalidations->removeElement($avisvalidation)) {
            // set the owning side to null (unless already changed)
            if ($avisvalidation->getConducteur() === $this) {
                $avisvalidation->setConducteur(null);
            }
        }

        return $this;
    }
}

<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: "L'email est obligatoire")]
    #[Assert\Email(message: "L'email '{{ value }}' n'est pas valide")]
    private ?string $email = null;

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\ManyToOne(targetEntity: Role::class, inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "Le rôle est obligatoire")]
    private ?Role $role = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $apiToken = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom est obligatoire")]
    #[Assert\Length(min: 2, max: 255, minMessage: "Le nom doit contenir au moins 2 caractères")]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le prénom est obligatoire")]
    #[Assert\Length(min: 2, max: 255, minMessage: "Le prénom doit contenir au moins 2 caractères")]
    private ?string $prenom = null;

    #[ORM\Column]
    private ?int $credits = 0;

    /**
     * @var Collection<int, Trajets>
     */
    #[ORM\OneToMany(targetEntity: Trajets::class, mappedBy: 'conducteur', orphanRemoval: true)]
    private Collection $trajets;

    /**
     * @var Collection<int, Trajetsencours>
     */
    #[ORM\OneToMany(targetEntity: Trajetsencours::class, mappedBy: 'conducteur', orphanRemoval: true)]
    private Collection $trajetsencours;

    /**
     * @var Collection<int, Trajetsfini>
     */
    #[ORM\OneToMany(targetEntity: Trajetsfini::class, mappedBy: 'conducteur')]
    private Collection $trajetsfinis;

    public function __construct()
    {
        $this->credits = 0;
        $this->trajets = new ArrayCollection();
        $this->trajetsencours = new ArrayCollection();
        $this->trajetsfinis = new ArrayCollection();
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
        $roles = [];
        
        if ($this->role) {
            $roleTitle = $this->role->getTitre();
            // S'assurer que le rôle a le format ROLE_XXX
            if (!str_starts_with($roleTitle, 'ROLE_')) {
                $roleTitle = 'ROLE_' . strtoupper($roleTitle);
            }
            $roles[] = $roleTitle;
        }
        
        // Garantir ROLE_USER si aucun rôle n'est défini
        if (empty($roles)) {
            $roles[] = 'ROLE_USER';
        }
        
        return array_unique($roles);
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function setRole(?Role $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getApiToken(): ?string
    {
        return $this->apiToken;
    }

    public function setApiToken(?string $apiToken): static
    {
        $this->apiToken = $apiToken;

        return $this;
    }

    /**
     * Génère un token API aléatoire
     */
    public function generateApiToken(): void
    {
        $this->apiToken = bin2hex(random_bytes(32));
    }

    /**
     * Supprime le token API
     */
    public function clearApiToken(): void
    {
        $this->apiToken = null;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getCredits(): ?int
    {
        return $this->credits;
    }

    public function setCredits(int $credits): static
    {
        $this->credits = $credits;

        return $this;
    }

    /**
     * Ajoute des crédits au compte utilisateur
     */
    public function addCredits(int $amount): static
    {
        $this->credits += $amount;
        return $this;
    }

    /**
     * Retire des crédits du compte utilisateur
     */
    public function subtractCredits(int $amount): static
    {
        $this->credits -= $amount;
        if ($this->credits < 0) {
            $this->credits = 0;
        }
        return $this;
    }

    /**
     * Méthode utilitaire pour afficher le nom complet
     */
    public function getFullName(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }

    /**
     * Méthode pour vérifier si l'utilisateur a un rôle spécifique
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles(), true);
    }

    /**
     * Vérifie si l'utilisateur a suffisamment de crédits
     */
    public function hasEnoughCredits(int $amount): bool
    {
        return $this->credits >= $amount;
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
            $trajet->setConducteur($this);
        }

        return $this;
    }

    public function removeTrajet(Trajets $trajet): static
    {
        if ($this->trajets->removeElement($trajet)) {
            // set the owning side to null (unless already changed)
            if ($trajet->getConducteur() === $this) {
                $trajet->setConducteur(null);
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
            $trajetsencour->setConducteur($this);
        }

        return $this;
    }

    public function removeTrajetsencour(Trajetsencours $trajetsencour): static
    {
        if ($this->trajetsencours->removeElement($trajetsencour)) {
            // set the owning side to null (unless already changed)
            if ($trajetsencour->getConducteur() === $this) {
                $trajetsencour->setConducteur(null);
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
            $trajetsfini->setConducteur($this);
        }

        return $this;
    }

    public function removeTrajetsfini(Trajetsfini $trajetsfini): static
    {
        if ($this->trajetsfinis->removeElement($trajetsfini)) {
            // set the owning side to null (unless already changed)
            if ($trajetsfini->getConducteur() === $this) {
                $trajetsfini->setConducteur(null);
            }
        }

        return $this;
    }
}
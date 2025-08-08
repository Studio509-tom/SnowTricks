<?php

namespace App\Entity;

use App\Repository\TrickRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Validator\Constraints\Json;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrickRepository::class)]
class Trick
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: "text")]
    private ?string $content = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $files = []; // Valeur par défaut vide

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $links = []; // Valeur par défaut vide

    #[ORM\ManyToOne(inversedBy: 'tricks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'trick', cascade: ['remove'])]
    private Collection $comments;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date_create = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_modify = null;

    #[ORM\Column(type: "json", nullable: true)]
    private ?array $first_file = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getFiles(): ?array
    {
        return $this->files;
    }

    public function setFiles(?array $files): static
    {
        $this->files = $files;

        return $this;
    }

    public function getLinks(): ?array
    {
        return $this->links;
    }

    public function setLinks(?array $links): static
    {
        $this->links = $links;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setTrick($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getTrick() === $this) {
                $comment->setTrick(null);
            }
        }

        return $this;
    }

    public function getDateCreate(): ?\DateTimeInterface
    {
        return $this->date_create;
    }

    public function setDateCreate(\DateTimeInterface $date_create): static
    {
        $this->date_create = $date_create;

        return $this;
    }

    public function getDateModify(): ?\DateTimeInterface
    {
        return $this->date_modify;
    }

    public function setDateModify(?\DateTimeInterface $date_modify): static
    {
        $this->date_modify = $date_modify;

        return $this;
    }

    public function getFirstFile(): ?array
    {
        return $this->first_file;
    }

    public function setFirstFile(?array $first_file): static
    {
        $this->first_file = $first_file;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }
    function slugify($text)
   {
      // Remplacer les accents par leur équivalent ASCII
      $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
      // Remplacer les espaces par des tirets
      $text = str_replace(' ', '-', $text);
      // Supprimer les caractères non-alphabétiques et non-numériques sauf les tirets
      $text = preg_replace('/[^a-zA-Z0-9\-]/', '', $text);
      // Remplacer plusieurs tirets consécutifs par un seul
      $text = preg_replace('/-+/', '-', $text);
      // Supprimer les tirets en début et fin de chaîne
      $text = trim($text, '-');
      // Passer en minuscules
      $text = strtolower($text);
      return $text;
   }
}

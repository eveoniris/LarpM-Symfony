<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User extends BaseUser implements UserInterface, PasswordAuthenticatedUserInterface, \Stringable
{
    public function __construct(string $email)
    {
        // @deprecated
        $this->salt = base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);

        $this->email = $email;
        $this->setCreationDate(new \DateTime('NOW'));
        parent::__construct();
    }

    // @deprecated
    public function addCoeur(): static
    {
        $coeur = $this->getCoeur();
        ++$coeur;
        $this->setCoeur($coeur);

        return $this;
    }


    /**
     * Recherche si l'utilisateur à un événement futur de prévu.
     */
    public function hasFuturEvent(): bool
    {
        $now = new \DateTime('NOW');
        foreach ($this->getParticipants() as $participant) {
            if ($participant->getGn()->getDateDebut() > $now) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fourni la liste de tous les événements futur auquel l'utilisateur participe.
     */
    public function getFuturEvents(): ArrayCollection
    {
        $futurEvents = new ArrayCollection();
        $now = new \DateTime('NOW');

        foreach ($this->getParticipants() as $participant) {
            if ($participant->getGn()->getDateDebut() > $now) {
                $futurEvents[] = $participant;
            }
        }

        return $futurEvents;
    }

    /**
     * Fourni les informations de participation à un jeu.
     */
    public function getParticipant(Gn $gn): ?Participant
    {
        foreach ($this->getParticipants() as $participant) {
            if ($participant->getGn() == $gn) {
                return $participant;
            }
        }

        return null;
    }

    /**
     * Retourne le dernier participant de l'utilisateur.
     */
    public function getLastParticipant(): ?Participant
    {
        if (!$this->getParticipants()->isEmpty()) {
            return $this->getParticipants()->last();
        }

        return null;
    }

    /**
     * Fourni tous les billets d'un utilisateur.
     */
    public function getBillets(): ArrayCollection
    {
        $billets = new ArrayCollection();
        foreach ($this->getParticipants() as $participant) {
            if ($participant->getBillet()) {
                $billets[] = $participant->getBillet();
            }
        }

        return $billets;
    }

    /**
     * Fourni la liste des tous les GN auquel l'utillisateur participe.
     */
    public function getGns(): ArrayCollection
    {
        $gns = new ArrayCollection();
        foreach ($this->getParticipants() as $participant) {
            $gns[] = $participant->getGn();
        }

        return $gns;
    }

    /**
     * Alias vers Username.
     */
    public function getName()
    {
        return $this->getUsername();
    }

    /**
     * Username + email.
     */
    public function getIdentity(): string
    {
        return $this->getUsername().' '.$this->getEmail();
    }

    /**
     * Indique si l'utilisateur participe à un GN.
     */
    public function takePart(Gn $gn): bool
    {
        foreach ($this->getParticipants() as $participant) {
            if ($participant->getGn() == $gn) {
                return true;
            }
        }

        return false;
    }

    /**
     * Indique si l'utilisateur est membre d'un groupe.
     */
    public function isMemberOf(Groupe $groupe)
    {
        return $this->getGroupes()->contains($groupe);
    }

    /**
     * Vérifie si un post est surveillé par l'utilisateur.
     */
    public function isInWatchingList(Post $post): bool
    {
        foreach ($this->getPosts() as $p) {
            if ($post == $p) {
                return true;
            }
        }

        return false;
    }

    /**
     * TODO Fourni la liste de tous les posts qu'un utilisateur n'a pas lu.
     */
    public function newPosts(Topic $topic): ArrayCollection
    {
        $newPosts = new ArrayCollection();
        foreach ($topic->getPosts() as $post) {
            if (!$this->alreadyView($post)) {
                $newPosts[] = $post;
            }
        }

        return $newPosts;
    }

    /**
     * Determine si l'utilisateur a déjà lu ce post.
     */
    public function alreadyView(Post $post): bool
    {
        foreach ($this->getPostViews() as $postView) {
            if ($postView->getPost() == $post) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine si l'une des réponses à un post n'a pas été lu par l'utilisateur.
     */
    public function newResponse(Post $post): bool
    {
        // construit la liste des posts déjà vue
        $alreadyView = new ArrayCollection();

        foreach ($this->getPostViews() as $postView) {
            $alreadyView[] = $postView->getPost();
        }

        foreach ($post->getPosts() as $p) {
            if (!$alreadyView->contains($p)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fourni la liste des groupes dont l'utilisateur est le scénariste.
     */
    public function getGroupeScenariste()
    {
        return $this->getGroupeRelatedByScenaristeIds();
    }

    /**
     * Test whether the User has the given role.
     *
     * @param string $role
     */
    public function hasRole(string $role): bool
    {
        return in_array(strtoupper($role), $this->getRoles(), true);
    }

    /**
     * Add the given role to the User.
     *
     * @param string $role
     */
    public function addRole($role): void
    {
        $role = strtoupper($role);

        if ('ROLE_USER' === $role) {
            return;
        }

        $roles = explode(',', (string) $this->rights);

        if (!$this->hasRole($role)) {
            $roles[] = $role;
        }

        $this->rights = implode(',', array_values($roles));
    }

    /**
     * Remove the given role from the User.
     *
     * @param string $role
     */
    public function removeRole($role): void
    {
        $roles = explode(',', (string) $this->rights);

        if (false !== $key = array_search(strtoupper($role), $roles, true)) {
            unset($roles[$key]);
            $this->rights = implode(',', array_values($roles));
        }
    }

    /**
     * Get the actual Username value that was set,
     * or null if no Username has been set.
     * Compare to getUsername, which returns the email if Username is not set.
     *
     * @return string|null
     *
     * @see getUsername
     */
    public function getRealUsername()
    {
        return $this->username;
    }

    /**
     * The Symfony Security component stores a serialized User object in the session.
     * We only need it to store the User ID, because the User provider's refreshUser() method is called on each request
     * and reloads the User by its ID.
     *
     * @see \Serializable::serialize()
     */
    public function serialize(): string
    {
        return serialize([
            $this->id,
        ]);
    }

    /**
     * Checks whether the User is enabled.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a DisabledException and prevent login.
     *
     * Users are enabled by default.
     *
     * @return bool true if the User is enabled, false otherwise
     *
     * @see DisabledException
     */
    public function isEnabled()
    {
        return $this->isEnabled;
    }

    public function setEnabled($isEnabled): static
    {
        return $this->setIsEnabled($isEnabled);
    }

    /**
     * @param int $ttl password reset request TTL, in seconds
     */
    public function isPasswordResetRequestExpired($ttl): bool
    {
        $timeRequested = $this->getTimePasswordResetRequested();
        if (0 === $timeRequested) {
            return true;
        }

        return $timeRequested + $ttl < time();
    }

    /**
     * Checks whether the User's credentials (password) has expired.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a CredentialsExpiredException and prevent login.
     *
     * @return bool true if the User's credentials are non expired, false otherwise
     *
     * @see CredentialsExpiredException
     */
    public function isCredentialsNonExpired(): bool
    {
        return true;
    }

    /**
     * Checks whether the User is locked.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a LockedException and prevent login.
     *
     * @return bool true if the User is not locked, false otherwise
     *
     * @see LockedException
     */
    public function isAccountNonLocked(): bool
    {
        return true;
    }

    /**
     * Checks whether the User's account has expired.
     *
     * Internally, if this method returns false, the authentication system
     * will throw an AccountExpiredException and prevent login.
     *
     * @return bool true if the User's account is non expired, false otherwise
     *
     * @see AccountExpiredException
     */
    public function isAccountNonExpired(): bool
    {
        return true;
    }

    public function equals(User $User): void
    {
    }

    /**
     * Removes sensitive data from the User.
     *
     * This is a no-op, since we never store the plain text credentials in this object.
     * It's required by UserInterface.
     */
    public function eraseCredentials(): void
    {
    }

    /**
     * Returns the name, if set, or else "Anonymous {id}".
     *
     * @return string
     */
    public function getDisplayName()
    {
        return $this->username ?: 'Anonymous '.$this->id;
    }

    /**
     * Test whether Username has ever been set (even if it's currently empty).
     */
    public function hasRealUsername(): bool
    {
        return !is_null($this->username);
    }

    /**
     * Validate the User object.
     *
     * @return array an array of error messages, or an empty array if there were no errors
     */
    public function validate(): array
    {
        $errors = [];

        if ('' === $this->getEmail() || '0' === $this->getEmail()) {
            $errors['email'] = 'Email address is required.';
        } elseif (!strpos($this->getEmail(), '@')) {
            // Basic email format sanity check. Real validation comes from sending them an email with a link they have to click.
            $errors['email'] = 'Email address appears to be invalid.';
        } elseif (strlen($this->getEmail()) > 100) {
            $errors['email'] = "Email address can't be longer than 100 characters.";
        }

        if ('' === $this->getPassword() || '0' === $this->getPassword()) {
            $errors['password'] = 'Password is required.';
        } elseif (strlen($this->getPassword()) > 255) {
            $errors['password'] = "Password can't be longer than 255 characters.";
        }

        if (strlen($this->getUsername()) > 100) {
            $errors['name'] = "Name can't be longer than 100 characters.";
        }

        // Username can't contain "@",
        // because that's how we distinguish between email and Username when signing in.
        // (It's possible to sign in by providing either one.)
        if ($this->getRealUsername() && str_contains($this->getRealUsername(), '@')) {
            $errors['Username'] = 'Username cannot contain the "@" symbol.';
        }

        return $errors;
    }

    public function getLastPersonnage()
    {
        $last = null;
        foreach ($this->getParticipants() as $participant) {
            if (null != $participant->getPersonnage() && (null == $last || $participant->getId() > $last->getId())) {
                // On conserve la dernière participation avec un personnage
                $last = $participant;
            }
        }

        return $last->getPersonnage();
    }

    /**
     * Retourne le nom complet de l'utilisateur (nom prénom).
     */
    public function getFullName(): string
    {
        return $this->getEtatCivil()->getFullName();
    }

    /**
     * @return mixed[]
     */
    public function getPersonnagesVivants(): array
    {
        $personnages_vivants = [];
        foreach ($this->personnages as $personnage) {
            if ($personnage->getVivant()) {
                $personnages_vivants[] = $personnage;
            }
        }

        return $personnages_vivants;
    }

    /**
     * Retourne l'age du joueur.
     */
    public function getAgeJoueur(): int
    {
        $etat_civil = $this->getEtatCivil();
        if (!$etat_civil) {
            return 33;
        }

        $naissance = $etat_civil->getDateNaissance();
        $gn_date = $this->getLastParticipant()->getGn()->getDateDebut();
        $interval = date_diff($gn_date, $naissance);

        return (int) $interval->format('%y');
    }
}

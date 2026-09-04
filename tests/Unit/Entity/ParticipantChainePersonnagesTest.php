<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Billet;
use App\Entity\Classe;
use App\Entity\Genre;
use App\Entity\Gn;
use App\Entity\Groupe;
use App\Entity\GroupeGn;
use App\Entity\Participant;
use App\Entity\Personnage;
use App\Entity\PersonnageSecondaire;
use App\Enum\PersonnageRoleType;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Chaîne de personnages d'une participation :
 * principal -> substitution (si l'opus l'active) -> relève -> archétype de secours.
 *
 * Règles couvertes ici :
 * - le rôle de substitution n'apparaît que si le GN active l'option ;
 * - ne pas choisir de substitution signifie que le principal endosse les deux rôles ;
 * - un personnage alternatif ne peut être choisi qu'avec billet ET personnage principal.
 */
#[Group('unit')]
class ParticipantChainePersonnagesTest extends TestCase
{
    public function testChaineSansOptionSubstitutionNExposePasLeRole(): void
    {
        $participant = $this->participant(substitutionActive: false);

        $roles = $this->roles($participant);

        self::assertSame(
            [PersonnageRoleType::PRINCIPAL, PersonnageRoleType::RELEVE, PersonnageRoleType::ARCHETYPE],
            $roles,
        );
    }

    public function testChaineAvecOptionSubstitutionInsereLeRoleApresLePrincipal(): void
    {
        $participant = $this->participant(substitutionActive: true);

        $roles = $this->roles($participant);

        self::assertSame(
            [
                PersonnageRoleType::PRINCIPAL,
                PersonnageRoleType::SUBSTITUTION,
                PersonnageRoleType::RELEVE,
                PersonnageRoleType::ARCHETYPE,
            ],
            $roles,
        );
    }

    public function testChaineExposeLesEntitesChoisies(): void
    {
        $principal = $this->personnage('Conan');
        $substitution = $this->personnage('Publio');
        $releve = $this->personnage('Balthus');
        $archetype = new PersonnageSecondaire();

        $participant = $this->participant(substitutionActive: true);
        $participant->setPersonnage($principal);
        $participant->setPersonnageSubstitution($substitution);
        $participant->setPersonnageReleve($releve);
        $participant->setPersonnageSecondaire($archetype);

        $chaine = $participant->getChainePersonnages();

        self::assertSame($principal, $chaine[0]['personnage']);
        self::assertSame($substitution, $chaine[1]['personnage']);
        self::assertSame($releve, $chaine[2]['personnage']);
        self::assertSame($archetype, $chaine[3]['archetype']);
        self::assertNull($chaine[3]['personnage']);
    }

    public function testLeLibelleDeLArchetypeEstAccordeAuGenreDuPrincipal(): void
    {
        $classe = (new Classe())->setLabelMasculin('Soldat')->setLabelFeminin('Soldate');
        $archetype = (new PersonnageSecondaire())->setClasse($classe);

        $participant = $this->participant(substitutionActive: false);
        $participant->setPersonnage($this->personnage('Valeria', 'Feminin'));
        $participant->setPersonnageSecondaire($archetype);

        self::assertSame('Soldate', $participant->getChainePersonnages()[2]['libelle']);
    }

    public function testLeLibelleDeLArchetypeEstAuMasculinParDefaut(): void
    {
        $classe = (new Classe())->setLabelMasculin('Soldat')->setLabelFeminin('Soldate');
        $archetype = (new PersonnageSecondaire())->setClasse($classe);

        $participant = $this->participant(substitutionActive: false);
        // Personnage sans genre renseigné : le masculin est la forme par défaut,
        // comme le fait déjà Personnage::getClasseName().
        $participant->setPersonnage($this->personnage('Conan'));
        $participant->setPersonnageSecondaire($archetype);

        self::assertSame('Soldat', $participant->getChainePersonnages()[2]['libelle']);
    }

    public function testLeLibelleDUnRoleNonPourvuEstNul(): void
    {
        $participant = $this->participant(substitutionActive: true);

        foreach ($participant->getChainePersonnages() as $maillon) {
            self::assertNull($maillon['libelle']);
        }
    }

    public function testSansSubstitutionLePrincipalEndosseLesDeuxRoles(): void
    {
        $principal = $this->personnage('Conan');

        $participant = $this->participant(substitutionActive: true);
        $participant->setPersonnage($principal);

        self::assertNull($participant->getPersonnageSubstitution());
        self::assertSame($principal, $participant->getPersonnageSubstitutionEffectif());
    }

    public function testAvecSubstitutionLeRoleEstTenuParLeSubstitut(): void
    {
        $principal = $this->personnage('Conan');
        $substitution = $this->personnage('Publio');

        $participant = $this->participant(substitutionActive: true);
        $participant->setPersonnage($principal);
        $participant->setPersonnageSubstitution($substitution);

        self::assertSame($substitution, $participant->getPersonnageSubstitutionEffectif());
    }

    public function testPersonnagesEngagesRegroupeLesTroisRolesReels(): void
    {
        $principal = $this->personnage('Conan');
        $substitution = $this->personnage('Publio');
        $releve = $this->personnage('Balthus');

        $participant = $this->participant(substitutionActive: true);
        $participant->setPersonnage($principal);
        $participant->setPersonnageSubstitution($substitution);
        $participant->setPersonnageReleve($releve);
        // L'archétype n'est pas un personnage : il ne fait pas partie des engagés.
        $participant->setPersonnageSecondaire(new PersonnageSecondaire());

        self::assertSame(
            [
                PersonnageRoleType::PRINCIPAL->value => $principal,
                PersonnageRoleType::SUBSTITUTION->value => $substitution,
                PersonnageRoleType::RELEVE->value => $releve,
            ],
            $participant->getPersonnagesEngages(),
        );
    }

    public function testPersonnagesEngagesIgnoreLesRolesNonPourvus(): void
    {
        $principal = $this->personnage('Conan');

        $participant = $this->participant(substitutionActive: true);
        $participant->setPersonnage($principal);

        self::assertSame([PersonnageRoleType::PRINCIPAL->value => $principal], $participant->getPersonnagesEngages());
    }

    public function testChoixAlternatifRefuseSansBillet(): void
    {
        $participant = $this->participant(substitutionActive: true);
        $participant->setPersonnage($this->personnage('Conan'));

        self::assertFalse($participant->peutChoisirPersonnageAlternatif());
    }

    public function testChoixAlternatifRefuseSansPersonnagePrincipal(): void
    {
        $participant = $this->participant(substitutionActive: true);
        $participant->setBillet(new Billet());

        self::assertFalse($participant->peutChoisirPersonnageAlternatif());
    }

    public function testChoixAlternatifAutoriseAvecBilletEtPrincipal(): void
    {
        $participant = $this->participant(substitutionActive: true);
        $participant->setBillet(new Billet());
        $participant->setPersonnage($this->personnage('Conan'));

        self::assertTrue($participant->peutChoisirPersonnageAlternatif());
    }

    public function testVerrouillageSuitLeGroupeDuParticipant(): void
    {
        $groupe = new Groupe();
        $groupeGn = new GroupeGn();
        $groupeGn->setGroupe($groupe);

        $participant = $this->participant(substitutionActive: false);
        $participant->setGroupeGn($groupeGn);

        self::assertFalse($participant->isVerrouille());

        $groupe->setLock(true);

        self::assertTrue($participant->isVerrouille());
    }

    public function testSansGroupeLaParticipationNEstPasVerrouillee(): void
    {
        $participant = $this->participant(substitutionActive: false);

        self::assertFalse($participant->isVerrouille());
    }

    private function participant(bool $substitutionActive): Participant
    {
        $gn = new Gn();
        $gn->setSubstitutionActive($substitutionActive);

        $participant = new Participant();
        $participant->setGn($gn);
        $participant->setGroupeGn(null);

        return $participant;
    }

    private function personnage(string $nom, ?string $genre = null): Personnage
    {
        $personnage = new Personnage();
        $personnage->setNom($nom);

        if (null !== $genre) {
            $personnage->setGenre((new Genre())->setLabel($genre));
        }

        return $personnage;
    }

    /** @return array<int, PersonnageRoleType> */
    private function roles(Participant $participant): array
    {
        return array_column($participant->getChainePersonnages(), 'role');
    }
}

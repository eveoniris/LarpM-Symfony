<?php

namespace App\Form;

use App\Entity\Participant;
use App\Enum\Role;
use App\Security\MultiRolesExpression;
use Doctrine\ORM\EntityManagerInterface;
use PhpParser\Node\Expr\AssignOp\Mul;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ParticipantForm extends AbstractType
{
    public function __construct(
        private readonly Security $security,
    )
    {
    }

    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('couchage', ChoiceType::class, [
            'label' => 'Type de couchage',
            'choices' => ['RP en jeu' => 'RP', 'HRP sur site' => 'HRP', 'Hors site de jeu' => 'HSJ'],
            'required' => true,
        ]);

        if (!$this->security->isGranted(new MultiRolesExpression(Role::ORGA, Role::SCENARISTE))) {
            return;
        }

        $builder->add('special', TextType::class, [
            'label' => 'Informations complémentaires (animaux, besoin médicaux, ...)',
        ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Participant::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'participant';
    }
}

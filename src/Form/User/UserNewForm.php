<?php


namespace App\Form\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * LarpManager\Form\User\UserNewForm.
 *
 * @author kevin
 */
class UserNewForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
            'label' => 'Adresse mail',
            'required' => true,
        ])
            ->add('Username', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'label' => "Nom d'utilisateur",
                'required' => true,
            ])
            ->add('gn', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'label' => 'Jeu auquel le nouvel utilisateur participe',
                'required' => false,
                'multiple' => false,
                'expanded' => true,
                'class' => \App\Entity\Gn::class,
                'property' => 'label',
            ])
            ->add('billet', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'label' => 'Choisissez le billet a donner à cet utilisateur',
                'multiple' => false,
                'expanded' => true,
                'required' => false,
                'class' => \App\Entity\Billet::class,
                'property' => 'fullLabel',
                'query_builder' => static function ($er) {
                    $qb = $er->createQueryBuilder('b');
                    $qb->orderBy('b.gn', 'ASC');

                    return $qb;
                },
            ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'UserNew';
    }
}

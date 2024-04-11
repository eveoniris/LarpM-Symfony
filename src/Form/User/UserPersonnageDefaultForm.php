<?php


namespace App\Form\User;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\User\UserPersonnageDefaultForm.
 *
 * @author kevin
 */
class UserPersonnageDefaultForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('personnage', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
            'required' => false,
            'label' => 'Choisissez votre personnage par défaut. Ce personnage sera utilisé pour signer vos messages',
            'multiple' => false,
            'expanded' => true,
            'class' => \App\Entity\Personnage::class,
            'choice_label' => 'identity',
            'placeholder' => 'Aucun',
            'empty_data' => null,
            'query_builder' => static function (EntityRepository $er) use ($options) {
                return $er->createQueryBuilder('p')
                    ->join('p.user', 'u')
                    ->where('u.id = :UserId')
                    ->setParameter('UserId', $options['User_id']);
            },
        ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\User::class,
            'User_id' => null,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'UserPersonnageDefault';
    }
}

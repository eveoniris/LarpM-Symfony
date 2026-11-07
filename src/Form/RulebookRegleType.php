<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\RulebookDomaine;
use App\Entity\RulebookRegle;
use App\Enum\RulebookStatut;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Édition d'une règle du rulebook. Les textes sont du markdown pur (éditeur EasyMDE côté navigateur, nettoyage côté
 * serveur). Le code n'est modifiable qu'à la création : les tests et les liens le citent.
 */
final class RulebookRegleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('domaine', EntityType::class, [
                'class' => RulebookDomaine::class,
                'choice_label' => 'titre',
                'label' => 'Domaine',
            ])
            ->add('code', TextType::class, [
                'label' => 'Code',
                'disabled' => !$options['creation'],
                'help' => 'Identifiant stable cité par les tests (ex: XP-03).',
                'constraints' => $options['creation']
                    ? [new NotBlank(), new Regex('/^[A-Z][A-Z0-9]*-[0-9]{2,3}$/', 'Format attendu : DOMAINE-NN (ex: XP-03).')]
                    : [],
            ])
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'constraints' => [new NotBlank(), new Length(max: 200)],
            ])
            ->add('statut', EnumType::class, [
                'class' => RulebookStatut::class,
                'choice_label' => static fn (RulebookStatut $statut): string => $statut->label(),
                'label' => 'Statut',
            ])
            ->add('publiee', CheckboxType::class, [
                'required' => false,
                'label' => 'Publiée',
                'help' => 'Un scénariste ne voit que les règles publiées, de statut valide, avec une version claire.',
            ])
            ->add('position', IntegerType::class, [
                'label' => 'Position dans le domaine',
                'help' => 'Les règles sont triées par position croissante.',
            ])
            ->add('texteClair', TextareaType::class, [
                'required' => false,
                'label' => 'Version claire (lue par les scénaristes)',
                'attr' => ['class' => 'js-markdown', 'rows' => 10],
            ])
            ->add('detailTechnique', TextareaType::class, [
                'required' => false,
                'label' => 'Détail technique (équipe règle)',
                'attr' => ['class' => 'js-markdown', 'rows' => 14],
            ])
            ->add('testsRefs', TextareaType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'Tests associés (un par ligne)',
                'attr' => ['rows' => 3],
            ])
            ->add('motif', TextType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'Motif de la modification',
                'help' => 'Conservé dans l\'historique de la règle.',
                'constraints' => [new Length(max: 255)],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RulebookRegle::class,
            'creation' => false,
        ]);
        $resolver->setAllowedTypes('creation', 'bool');
    }
}

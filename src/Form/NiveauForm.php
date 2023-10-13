<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class NiveauForm extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->add('label','text', array(
					'required' => true,	)
				)
				->add('niveau','integer', array(
					'required' => true,	)
				)
				->add('cout_favori','integer', array(
						'label' => "Coût favori",
						'required' => true,	)
				)
				->add('cout','integer', array(
					'label' => "Coût normal",
					'required' => true,	)
				)
				->add('cout_meconu','integer', array(
					'label' => "Coût méconnu",
					'required' => true,	)
				);
	}
	
	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		$resolver->setDefaults(array(
				'data' => 'App\Entity\Niveau',
		));
	}
	
	public function getName()
	{
		return 'niveauForm';
	}
}
<?php

namespace App\Form;

use App\Entity\Outcome;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OutcomeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, [
                'label' => false,
                'attr'  => [
                    'placeholder' => 'Issue (ex: Victoire équipe A)',
                    'class'       => 'field-luxury',
                ],
            ])
            ->add('odds', NumberType::class, [
                'label' => false,
                'scale' => 2,
                'attr'  => [
                    'placeholder' => 'Cote (ex: 2.50)',
                    'step'        => '0.01',
                    'min'         => '1.01',
                    'class'       => 'field-luxury',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Outcome::class]);
    }
}

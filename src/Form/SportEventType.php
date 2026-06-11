<?php

namespace App\Form;

use App\Entity\SportEvent;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class SportEventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de l\'événement',
                'attr'  => [
                    'placeholder' => 'Ex: Paris Saint-Germain vs Real Madrid',
                    'class'       => 'field-luxury',
                ],
            ])
            ->add('sport', ChoiceType::class, [
                'label'   => 'Sport',
                'choices' => [
                    'Football'   => 'Football',
                    'Basketball' => 'Basketball',
                    'Tennis'     => 'Tennis',
                    'Rugby'      => 'Rugby',
                    'Handball'   => 'Handball',
                    'Volleyball' => 'Volleyball',
                    'Cyclisme'   => 'Cyclisme',
                    'Athlétisme' => 'Athletisme',
                    'Natation'   => 'Natation',
                    'Boxe'       => 'Boxe',
                    'MMA'        => 'MMA',
                    'Autre'      => 'Autre',
                ],
                'attr' => ['class' => 'field-luxury'],
            ])
            ->add('participantsText', TextareaType::class, [
                'label'       => 'Participants (un par ligne)',
                'mapped'      => false,
                'required'    => false,
                'constraints' => [new NotBlank(message: 'Ajoutez au moins un participant.')],
                'attr'        => [
                    'rows'        => 3,
                    'placeholder' => "Équipe A\nÉquipe B",
                    'class'       => 'field-luxury',
                ],
            ])
            // Raw text fields — type="date"/"time" is set in Twig via form_widget variables
            ->add('eventDatePart', TextType::class, [
                'label'    => 'Date',
                'mapped'   => false,
                'required' => false,
                'attr'     => ['class' => 'field-luxury'],
            ])
            ->add('eventTimePart', TextType::class, [
                'label'    => 'Heure',
                'mapped'   => false,
                'required' => false,
                'attr'     => ['class' => 'field-luxury'],
            ])
            ->add('outcomes', CollectionType::class, [
                'entry_type'     => OutcomeType::class,
                'allow_add'      => true,
                'allow_delete'   => true,
                'by_reference'   => false,
                'label'          => false,
                'prototype'      => true,
                'prototype_name' => '__name__',
            ])
        ;

        // Populate unmapped fields from the entity when editing
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $sportEvent = $event->getData();
            $form       = $event->getForm();

            dump($sportEvent);

            if (!$sportEvent instanceof SportEvent) {
                return;
            }

            if ($sportEvent->getParticipants()) {
                $form->get('participantsText')->setData(
                    implode("\n", $sportEvent->getParticipants())
                );
            }

            if ($sportEvent->getEventDate()) {
                $form->get('eventDatePart')->setData($sportEvent->getEventDate()->format('Y-m-d'));
                $form->get('eventTimePart')->setData($sportEvent->getEventDate()->format('H:i'));
            }
        });

        // Combine raw strings back to entity fields after submit
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            $sportEvent = $event->getData();
            $form       = $event->getForm();

            if (!$sportEvent instanceof SportEvent) {
                return;
            }

            // Participants
            $text         = $form->get('participantsText')->getData() ?? '';
            $participants = array_values(array_filter(array_map('trim', explode("\n", $text))));
            $sportEvent->setParticipants($participants);

            // Combine date + time strings into a DateTime
            $dateStr = trim($form->get('eventDatePart')->getData() ?? '');
            $timeStr = trim($form->get('eventTimePart')->getData() ?? '');

            if ($dateStr !== '' && $timeStr !== '') {
                $combined = \DateTime::createFromFormat('Y-m-d H:i', $dateStr . ' ' . $timeStr);
                $sportEvent->setEventDate($combined ?: null);
            } else {
                $sportEvent->setEventDate(null);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => SportEvent::class]);
    }
}

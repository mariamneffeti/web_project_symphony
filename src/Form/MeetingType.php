<?php

namespace App\Form;

use App\Entity\Meeting;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MeetingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'attr' => ['placeholder' => 'e.g. Weekly Team Sync', 'id' => 'newTitle']
            ])
            ->add('status', ChoiceType::class, [
                'choices'  => [
                    'Scheduled' => 'scheduled',
                    'Done' => 'done',
                    'Cancelled' => 'cancelled',
                ],
                'attr' => ['id' => 'newStatus']
            ])
            ->add('meeting_date', DateType::class, [
                'widget' => 'single_text',
                'attr' => ['id' => 'newDate']
            ])
            ->add('meeting_time', TimeType::class, [
                'widget' => 'single_text',
                'attr' => ['id' => 'newTime']
            ])
            ->add('meet_link', TextType::class, [
                'required' => false,
                'attr' => ['placeholder' => 'https://meet.google.com/...', 'id' => 'newLink']
            ])
            ->add('notes', TextareaType::class, [
                'required' => false,
                'attr' => ['rows' => 2, 'placeholder' => 'Additional notes...', 'id' => 'newNotes']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Meeting::class,
        ]);
    }
}
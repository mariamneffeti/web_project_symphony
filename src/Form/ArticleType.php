<?php

namespace App\Form;

use App\Entity\Article;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;

class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Article Title',
                'attr'  => ['placeholder' => 'Enter title', 'class' => 'form-control'],
                'constraints' => [new NotBlank()],
            ])
            ->add('category', ChoiceType::class, [
                'label'   => 'Category / Tag',
                'choices' => [
                    'Choose...'    => '',
                    'Technology'   => 'Technology',
                    'Data & AI'    => 'Data & AI',
                    'Cybersecurity'=> 'Cybersecurity',
                    'HR & Careers' => 'HR & Careers',
                    'Company News' => 'Company News',
                ],
                'attr' => ['class' => 'form-select'],
                'constraints' => [new NotBlank()],
            ])
            ->add('description', TextareaType::class, [
                'label'    => 'Description',
                'required' => false,
                'attr'     => ['placeholder' => 'Article description', 'rows' => 4, 'class' => 'form-control'],
            ])
            ->add('authorName', TextType::class, [
                'label' => 'Author',
                'attr'  => ['placeholder' => 'Author name', 'class' => 'form-control'],
                'constraints' => [new NotBlank()],
            ])
            ->add('arDate', DateType::class, [
                'label'    => 'Publish Date',
                'widget'   => 'single_text',
                'required' => false,
                'attr'     => ['class' => 'form-control'],
            ])
            ->add('link', UrlType::class, [
                'label'    => 'Content Link',
                'required' => false,
                'attr'     => ['placeholder' => 'https://example.com/article.pdf', 'class' => 'form-control'],
                'constraints' => [new Url()],
            ])
            ->add('arImage', UrlType::class, [
                'label'    => 'Cover Image',
                'required' => false,
                'attr'     => ['placeholder' => 'https://example.com/image.jpg', 'class' => 'form-control'],
                'constraints' => [new Url()],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Article::class]);
    }
}

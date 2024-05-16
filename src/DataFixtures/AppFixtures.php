<?php

namespace App\DataFixtures;


use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use App\Entity\Post;
use App\Entity\Category;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('en_US');

        for ($i=1; $i<=5; $i++){
            $category = new Category();
            $category->setTitle($faker->word);
            $manager->persist($category);
            // Add post
            for ($j=1; $j <= 7; $j++){
                $post = new Post();
                $post->setTitle($faker->sentence(100));
                $post->setContent($faker->text(500));
                $post->setCreatedAt($faker->dateTimeBetween('- 3 months'));
                $post->setCategory($category);
                $manager->persist($post);
            }
        }
        $manager->flush();
    }
}

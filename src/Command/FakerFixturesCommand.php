<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Entity\Author;
use App\Entity\Book;

class FakerFixturesCommand extends Command
{
    protected static $defaultName = 'app:fixtures:load';

    /** @var SymfonyStyle */
    protected $io;
    /** @var \Faker\Generator **/
    protected $faker;
    /** @var ProgressIndicator **/
    protected $progress;
    /** @var \Doctrine\Bundle\DoctrineBundle\Registry **/
    protected $doctrine;
    /** @var UserPasswordEncoderInterface **/
    protected $passwordEncoder;

    public function __construct(ManagerRegistry $doctrine, UserPasswordEncoderInterface $passwordEncoder, $name = null)
    {
        parent::__construct($name);
        $this->faker = \Faker\Factory::create("en_US");
        $this->doctrine = $doctrine;
        $this->passwordEncoder = $passwordEncoder;
    }

    protected function configure()
    {
        $this->setDescription('Load all fixtures');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $confirmed = $this->io->confirm('This will delete all your database datas. Continue?', false);
        if (!$confirmed){
            $this->io->text("Ok then.");
            return 0;
        }

        $this->progress = new ProgressIndicator($output);
        $this->progress->start('Loading fixtures');

        //empty all tables, reset ids for Corentin
        $this->truncateTables();

        //order might be important
        //change argument to load more or less of each entity
        $this->loadAuthors($num = 500);
        $this->loadBooks($num = 500);

        //now loading ManyToMany data
        $this->progress->setMessage("loading many to many datas");
        $this->loadManyToManyData();

        $this->progress->finish("Done!");
        $this->io->success('Fixtures loaded!');
        return 0;
    }

    protected function loadAuthors(int $num): void
    {
        $this->progress->setMessage("loading authors");
        for($i=0; $i<$num; $i++){
            $author = new Author();

            $author->setFirstname( $this->faker->optional($chancesOfValue = 0.5, $default = null)->firstName );
            $author->setLastname( $this->faker->lastName );

            $this->doctrine->getManager()->persist($author);
            $this->progress->advance();
    }

        $this->doctrine->getManager()->flush();
    }

    protected function loadBooks(int $num): void
    {
        $this->progress->setMessage("loading books");
        for($i=0; $i<$num; $i++){
            $book = new Book();

            $book->setTitle( $this->faker->sentence($nbWords = $this->faker->randomDigitNot(0), $variableNbWords = false) );
            $book->setPagesCount( $this->faker->optional($chancesOfValue = 0.9, $default = null)->numberBetween($min = 100, $max = 3000) );
            $book->setCover( $this->faker->optional($chancesOfValue = 0.9, $default = null)->text(255) );
            $book->setCreatedDate( $this->faker->dateTimeBetween($startDate = "- 3 months", $endDate = "now") );

            $this->doctrine->getManager()->persist($book);
            $this->progress->advance();
    }

        $this->doctrine->getManager()->flush();
    }


    protected function truncateTables()
    {
        $this->progress->setMessage("Truncating tables");

        try {
            $connection = $this->doctrine->getConnection();
            $connection->beginTransaction();
            $connection->query("SET FOREIGN_KEY_CHECKS = 0");

            $connection->query("TRUNCATE author");
            $connection->query("TRUNCATE book");
            $connection->query("TRUNCATE book_author");

            $connection->query("SET FOREIGN_KEY_CHECKS = 1");
            $connection->commit();
        }
        catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    protected function loadManyToManyData()
    {
        $allAuthors = $this->doctrine->getRepository(Author::class)->findAll();
        $allBooks = $this->doctrine->getRepository(Book::class)->findAll();

        //loading data in book_author table
        foreach($allBooks as $book){
            $numberOfauthors = $this->faker->numberBetween($min = 1, $max = 5);
            //reset faker uniqueness
            $this->faker->unique(true)->randomElement([]);

            for($n = 0; $n < $numberOfauthors; $n++){
                $book->addAuthor( $this->faker->unique()->randomElement($allAuthors) );
            }

            $this->doctrine->getManager()->persist($book);
            $this->progress->advance();
        }

        $this->doctrine->getManager()->flush();

    }
}
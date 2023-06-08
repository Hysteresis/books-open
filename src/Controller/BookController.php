<?php

namespace App\Controller;

use App\Entity\Author;
use App\Entity\Book;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Hateoas\Serializer\SerializerInterface as SerializerSerializerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
// use JMS\Serializer\Serializer;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class BookController extends AbstractController
{
    #[Route('/api/books', name: 'app_book', methods: ['GET'])]
    public function getAllBooks(
        BookRepository $bookRepository, 
        SerializerInterface $serializer,
        Request $request,
        TagAwareCacheInterface $cachePool,
        ): JsonResponse
    {
        $page = $request->get("page", 1);
        $limit = $request->get("limit", 3);
        // nommer la requete que l'on veut mettre en cache
        // je créé un identifiant
        $idCache = "getAllBooks-" . $page . "-" . $limit;

        //? Mise en cache de la requête
        // si idCache est deja en cache on recupère l'élément qui est mis en cache
        // Sinon on joue la fonction avec ITemInterface et on met le resultat en cache et on le retourne
        $jsonBookList = $cachePool->get($idCache, function (ItemInterface $item) use ($bookRepository, $page, $limit, $serializer) {
            $item->tag("booksCache");
            $bookList = $bookRepository->findAllWithPagination($page, $limit);
            $context = SerializationContext::create()->setGroups(['getBooks']);
            return $serializer->serialize($bookList, 'json', $context);
        });



        return new JsonResponse(
            $jsonBookList,
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/api/books/{id}', name: 'app_book_id', methods: ['GET'])]
    public function getOneBook(
        Book $book,
        SerializerInterface $serializer,
        ): JsonResponse
    {

        $context = SerializationContext::create()->setGroups(['getBooks']);
        $jsonBook = $serializer->serialize($book, 'json', $context );
        
        return new JsonResponse(
            $jsonBook,
            Response::HTTP_OK,
            ['accept' => 'json'],
            true
        );
    }

    #[Route('/api/books/{id}', name: 'app_book_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits pour supprimer un livre')]
    public function deleteBook(
        Book $book, 
        EntityManagerInterface $em,
        TagAwareCacheInterface $cachePool,
        ): JsonResponse
    {
        $cachePool->invalidateTags(['booksCache']);
        $em->remove($book);
        $em->flush();

        return new JsonResponse(
            null,
            Response::HTTP_NO_CONTENT,
        );
    }

    #[Route('/api/books', name: 'app_book_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisant pour créer un livre')]
    public function createBook(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        AuthorRepository $authorRepository,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        // tranforme json en objet $book
        $book = $serializer->deserialize($request->getContent(), Book::class, 'json' );

        //verifier si il y a des erreurs
        $errors = $validator->validate($book);
        if($errors->count() > 0){
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        
        $em->persist($book);
        $em->flush();
        
        // recuperer les donnes de la requete et les stocker dans un tableau
        $content = $request->toArray();
        // recuperer l'id author et s'il nest pas défini on met -1 par défaut
        $idAuthor = $content['idAuthor'] ?? -1;

        $book->setAuthor($authorRepository->find($idAuthor));
        
        //on renvoie le $book en json dans la response car c'est une pratique
        $context = SerializationContext::create()->setGroups(['getBooks']);
        $jsonBook = $serializer->serialize($book, 'json', $context);

        //on creer l'url sur laquelle le livre peut etre trouvé
        $location = $urlGenerator->generate(
            'app_book_id',
            ['id' => $book->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return new JsonResponse(
            $jsonBook,
            Response::HTTP_CREATED,
            ['Location' => $location],
            true
        );
    }

    #[Route('/api/books/{id}', name:"app_book_update", methods:['PUT'])]
    public function updateBook(
        Request $request, 
        SerializerInterface $serializer,
        Book $currentBook, EntityManagerInterface $em, AuthorRepository $authorRepository, 
        ValidatorInterface $validator,
        TagAwareCacheInterface $cache,
        ): JsonResponse 
        {

        $newBook = $serializer->deserialize($request->getContent(), Book::class, 'json');

        $currentBook->setTitle($newBook->getTitle());
        $currentBook->setCoverText($newBook->getTitle());

        // On vérifie les erreurs
        $errors = $validator->validate($currentBook);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $content = $request->toArray();
        $idAuthor = $content['idAuthor'] ?? -1;

        $currentBook->setAuthor($authorRepository->find($idAuthor));

        $em->persist($currentBook);
        $em->flush();
        //on vide le cache
        $cache->invalidateTags(["booksCache"]);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}

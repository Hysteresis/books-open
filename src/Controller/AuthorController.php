<?php

namespace App\Controller;

use App\Entity\Author;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class AuthorController extends AbstractController
{
    #[Route('/api/authors', name: 'app_author', methods: ['GET'])]
    public function getAllAuthor(
        AuthorRepository $authorRepository, 
        SerializerInterface $serializer,
        Request $request,
        TagAwareCacheInterface $cache,
        ): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $idCache = "getAllAuthor-" . $page . "-" . $limit;

        $jsonAuthorList = $cache->get($idCache, function(ItemInterface $item) use($authorRepository, $page, $limit, $serializer){
            $item->tag("authorCache");
            $authorList = $authorRepository->findAllWithPagination($page, $limit);
            $context = SerializationContext::create()->setGroups(['getAuthors']);
            return $serializer->serialize($authorList, 'json', $context);
        });

        

        // $jsonAuthorList = $serializer->serialize($authorList, 'json', ['groups' => 'getAuthors']);

        return new JsonResponse(
            $jsonAuthorList,
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/api/authors/{id}', name: 'app_author_id', methods: ['GET'])]
    public function getOneAuthor(
        Author $author, 
        SerializerInterface $serializer,
        ): JsonResponse
    {
        $context = SerializationContext::create()->setGroups(['getAuthors']);
        $jsonAuthorList = $serializer->serialize($author, 'json', $context);

        return new JsonResponse(
            $jsonAuthorList,
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/api/authors/{id}', name: 'app_author_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits pour effacer un auteur')]
    public function deleteAuthor(
        Author $author,
        EntityManagerInterface $em,
        TagAwareCacheInterface $cache,
        ): JsonResponse {
        
        $cache->invalidateTags(['authorCache']);

        $em->remove($author);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/authors/{id}', name:"app_author_update", methods:['PUT'])]
    public function updateAuthor(
        Request $request, 
        SerializerInterface $serializer,
        Author $currentAuthor, 
        EntityManagerInterface $em,
        TagAwareCacheInterface $cache,
        ): JsonResponse {

        // $updatedAuthor = $serializer->deserialize($request->getContent(), Author::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentAuthor]);
        $newAuthor = $serializer->deserialize($request->getContent(), Author::class, 'json');

        $currentAuthor->setLastname($newAuthor->getTitle());
        $currentAuthor->setFirstname($newAuthor->getTitle());

        $em->persist($currentAuthor);
        $em->flush();
        $cache->invalidateTags(["authorCache"]);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/api/authors', name: 'app_author_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits pour créer un auteur')]
    public function createAuthor(
        Request $request, 
        SerializerInterface $serializer,
        EntityManagerInterface $em, 
        UrlGeneratorInterface $urlGenerator
        ): JsonResponse 
        {

        $author = $serializer->deserialize($request->getContent(), Author::class, 'json');

        $em->persist($author);
        $em->flush();
        $context = SerializationContext::create()->setGroups(['getAuthors']);
        $jsonAuthor = $serializer->serialize($author, 'json', $context);
        $location = $urlGenerator->generate('app_author_id', ['id' => $author->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonAuthor, Response::HTTP_CREATED, ["Location" => $location], true);	
    }

}

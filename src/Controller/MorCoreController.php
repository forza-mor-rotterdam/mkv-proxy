<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class MorCoreController extends AbstractController
{
    public function __construct(
        protected ?string $morCoreSecret,
        protected ?string $morCoreDomain,
        protected ?string $proxyDomain
    )
    {
        //
    }

    #[Route('/api-token-auth/', methods: ['POST'])]
    public function apiTokenAuth(Request $request, HttpClientInterface $morCoreClient): Response
    {
        return $this->mapResponse($morCoreClient->request('POST', '/api-token-auth/', [
            'body' => [
                'username' => $request->request->get('username') . '_via_proxy@forzamor.nl',
                'password' => $request->request->get('password') . $this->morCoreSecret,
            ]
        ]));
    }

    #[Route('/api/v1/melding/', methods: ['GET'])]
    public function meldingen(Request $request, HttpClientInterface $morCoreClient): Response
    {
        // only some specific query parameters are allowed, error on others
        foreach ($request->query->all() as $name => $value) {
            if (in_array($name, ['urgentie_gte', 'limit', 'offset', 'status']) === false) {
                return new JsonResponse(['status' => 'QUERY_DENIED', 'query_string_not_allowed' => $name], JsonResponse::HTTP_FORBIDDEN);
            }
        }

        // check if required query strings are set
        if ($request->query->has('urgentie_gte') === false) {
            return new JsonResponse(['status' => 'QUERY_DENIED', 'missing_query_string' => 'urgentie_gte'], JsonResponse::HTTP_FORBIDDEN);
        }

        // check if query strings have the right values
        if (floatval($request->query->get('urgentie_gte')) < 0.5) {
            return new JsonResponse(['status' => 'QUERY_DENIED', 'invalid_query_string' => 'urgentie_gte'], JsonResponse::HTTP_FORBIDDEN);
        }

        $response = $this->mapResponse($sourceApiResponse = $morCoreClient->request('GET', '/api/v1/melding/', [
            'query' => $request->query->all(),
            'headers' => [
                'Authorization' => $request->headers->get('Authorization')
            ]
        ]));

        // rewrite JSON
        if ($sourceApiResponse->getStatusCode() === 200) {
            $content = $sourceApiResponse->toArray();

            if (is_string($content['next'])) {
                $content['next'] = str_replace($this->morCoreDomain, $this->proxyDomain, $content['next']);
            }
            if (is_string($content['previous'])) {
                $content['previous'] = str_replace($this->morCoreDomain, $this->proxyDomain, $content['previous']);
            }

            foreach ($content['results'] as $i => $result) {
                // remove all fields in each results that are not listed
                $content['results'][$i] = array_filter($result, fn ($key) => in_array($key, ['id', 'uuid', 'aangemaakt_op', 'origineel_aangemaakt', 'urgentie', 'onderwerpen', 'onderwerp', 'locaties_voor_melding', 'signalen_voor_melding']), ARRAY_FILTER_USE_KEY);

                // remove all fields in locaties_voor_melding that are not listed
                foreach ($content['results'][$i]['locaties_voor_melding'] as $j => $locatie) {
                    $content['results'][$i]['locaties_voor_melding'][$j] = array_filter($locatie, fn ($key) => in_array($key, ['straatnaam', 'huisnummer', 'huisletter', 'toevoeging', 'postcode', 'plaatsnaam', 'primair']), ARRAY_FILTER_USE_KEY);
                }

                // remove all locaties that are not primair
                $content['results'][$i]['locaties_voor_melding'] = array_values(array_filter($content['results'][$i]['locaties_voor_melding'], fn ($locatie) => boolval($locatie['primair']) === true));


                // remove all fields in signalen_voor_melding that are not listed
                foreach ($content['results'][$i]['signalen_voor_melding'] as $j => $locatie) {
                    $content['results'][$i]['signalen_voor_melding'][$j] = array_filter($locatie, fn ($key) => in_array($key, ['bron_id', 'bron_signaal_id']), ARRAY_FILTER_USE_KEY);
                }
            }

            $response->setContent(json_encode($content));
        }

        return $response;
    }

    #[Route('/{all}', requirements: [
        'all' => '.*'
    ])]
    public function catchAll(): Response
    {
        return new JsonResponse(['status' => 'NOT_ALLOWED'], JsonResponse::HTTP_FORBIDDEN);
    }

    private function mapResponse(ResponseInterface $source): Response
    {
        $source->getHeaders(false); // force waiting on request

        $response = new Response();
        $response->setStatusCode($source->getStatusCode());
        $response->setContent($source->getContent(false));

        // rebuild header set
        $headers = [];
        foreach ($source->getHeaders(false) as $header => $values) {
            // redirecting some headers from the source to the client
            if (in_array($header, ['content-type'])) {
                // change values in some headers
                if ($header === 'location') {
                    $values = array_map(fn($value) => str_replace($this->morCoreDomain, $this->proxyDomain, $value), $values);
                }
                $headers[$header] = $values;
            }
        }
        $response->headers->add($headers);

        return $response;
    }
}
<?php

namespace App\Http\Controllers;

use AWS;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\JsonResponse;

class GoogleShoppingController extends Controller {

    private $dynamodb;
    private $marshaler;
    private $client;

    const GOOGLE_SHOP_ITEMS_TABLE = 'GoogleShoppingClientEventItemsTable';
    const SEND_MESSAGE = 'sendMessage';
    const SEND_URL = 'https://nu7xnyqjqi.execute-api.ap-southeast-1.amazonaws.com/beta/';

    public function __construct() {
        $this->dynamodb = AWS::createClient('DynamoDb');
        $this->marshaler = new Marshaler();
        $this->client = new \GuzzleHttp\Client();
    }

    /**
     * Add new item
     */
    public function addItem(string $channelId, string $title): \Illuminate\Http\JsonResponse {
        $websocketData = [
            'action' => self::SEND_MESSAGE,
            'channelId' => $channelId,
            'eventTitle' => 'Click',
            'content' => $title,
        ];

        $client = new Client(['base_uri' => self::SEND_URL]);
        try {
            $response = $client->request('POST', 'send-message', [
                'body' => json_encode($websocketData)
            ]);
        } catch (RequestException $e) {
            return response()->json([
                'error' => $e->getMessage() . PHP_EOL . $e->getRequest()->getMethod()
            ], $e->getResponse()->getStatusCode());
        }
        return response()->json($websocketData, $response->getStatusCode());
    }

    /**
     * Get items by client id
     */
    public function getItems(string $channelId): \Illuminate\Http\JsonResponse {
        try {
            // Scan all items with provided client id
            // $result = $this->dynamodb->scan([
            //     'TableName' => self::GOOGLE_SHOP_ITEMS_TABLE,
            //     'ScanIndexForward' => false,
            //     'Limit' => 100,
            //     'ScanFilter' => [
            //         'clientId' => [
            //             'AttributeValueList' => [
            //                 ['S' => $channelId],
            //             ],
            //             'ComparisonOperator' => 'EQ'
            //         ],
            //     ]
            // ]);

            $request = [
                'TableName' => self::GOOGLE_SHOP_ITEMS_TABLE,
                'IndexName' => 'clientId-timestamp-index',
                'KeyConditionExpression' => 'clientId = :clientId ',
                'ScanIndexForward' => false, // descending sort by range key
                'ExpressionAttributeValues' =>  [
                    ':clientId' => ['S' => $channelId]
                ],
                'Limit' => 100
            ];
            $result = $this->dynamodb->query($request);
            $data = $this->formatListData($result['Items']);
        } catch (DynamoDbException $e) {
            return response()->json([
                'error' => 'Unable to query' . PHP_EOL . $e->getMessage()
            ], $e->getResponse()->getStatusCode());
        }
        return response()->json($data, 200);
    }

    /**
     * Search item
     */
    public function searchItem(string $channelId, string $search): \Illuminate\Http\JsonResponse {
        $request = [
            'TableName' => self::GOOGLE_SHOP_ITEMS_TABLE,
            'IndexName' => 'clientId-eventTitle-searchIndex',
            'KeyConditionExpression' => 'clientId = :clientId and begins_with(eventTitle, :eventTitle)',
            'ScanIndexForward' => false, // descending sort by range key
            'ExpressionAttributeValues' =>  [
                ':clientId' => ['S' => $channelId],
                ':eventTitle' => ['S' => $search],
            ],
            'Limit' => 100
        ];
        $result = $this->dynamodb->query($request);
        $data = [
            'items' => $this->formatListData($result['Items']),
            'items_count' => $result['Count']
        ];
        return response()->json($data);
    }

    /**
     * Delete item
     */
    public function removeItem(string $id, string $channelId) {
        $key = $this->marshaler->marshalJson('{"clientId": "' . $channelId . '"}');
        $eav = $this->marshaler->marshalJson('{":val": "' . $channelId . '"}');

        $params = [
            'TableName' => self::GOOGLE_SHOP_ITEMS_TABLE,
            'IndexName' => 'clientId-timestamp-index',
            'Key' => ['id' => ['S' => $id]], 
            'ConditionExpression' => 'id = :val'
        ];
        
        try {
            $this->dynamodb->deleteItem($params);
            return response()->json([
                'status' => true
            ], 200);
        
        } catch (DynamoDbException $e) {
            return response()->json([
                'error' => 'Unable to delete item' . PHP_EOL . $e->getMessage()
            ], $e->getResponse()->getStatusCode());
        }
    }

    private function formatListData(array $items): array {
        $data = [];
        foreach ($items as $key => $item) {
            $data[$key]['id'] = $this->marshaler->unmarshalValue($item['id']);
            $data[$key]['clientId'] = $this->marshaler->unmarshalValue($item['clientId']);
            $data[$key]['eventTitle'] = $this->marshaler->unmarshalValue($item['eventTitle']);
            $data[$key]['content'] = $this->marshaler->unmarshalValue($item['content']);
            $data[$key]['timestamp'] = $this->marshaler->unmarshalValue($item['timestamp']);
        }
        return $data;
    }
}

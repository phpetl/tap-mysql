<?php
declare(strict_types=1);

namespace PhpETL\Tap\MySQL;

use Aura\SqlQuery\QueryFactory;

class Tap
{
    public function __construct(protected Config $config, protected \PDO $pdo)
    {
        
    }

    public function tap()
    {
        $catalogs = json_decode(file_get_contents(__DIR__ . '/../data/base_catalog.json'), true);
        $output = '';
        $state = ['type' => 'STATE', 'value' => []];

        foreach($catalogs['streams'] as $stream) {
            $schemaRecord = [
                'type' => 'SCHEMA',
                'stream' => $stream['stream'],
                'tap_stream_id' => $stream['tap_stream_id'],
                'schema' => $stream['schema'],
                'key_properties' => $stream['key_properties'],
                'bookmark_properties' => $stream['bookmark_properties'],
            ];
            $output .= json_encode($schemaRecord) . PHP_EOL;
            $state['value'][$stream['stream']] = 0;

            $now = new \DateTimeImmutable();

            $baseRecord = [
                'type' => 'RECORD',
                'stream' => $stream['stream'],
                'time_extracted' => $now->format(DATE_ISO8601),
                'record' => []
            ];

            $factory = new QueryFactory('mysql');
            $select = $factory->newSelect();
            $select
                ->from($stream['stream'])
                ->cols(array_keys($stream['schema']['properties']));
            $stmt = $this->pdo->prepare($select->getStatement());
            $stmt->execute();

            while ($data = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $recordData = [];
                foreach ($stream['schema']['properties'] as $columnName => $settings) {
                    $value = $data[$columnName];
                    switch($settings['type']) {
                        case 'bool':
                        case 'boolean':
                            $value = (bool) (string) $value;
                            break;
                        case \DateTime::class:
                        case 'datetime':
                            $value = new \DateTime((string) $value);
                            break;
                        case 'float':
                            $value = (float) (string) $value;
                            break;
                        case 'guid':
                            $value = trim((string) $value, '{}');
                            break;
                        case 'int':
                        case 'integer':
                        case 'number':
                            $value = (int) (string) $value;
                            break;
                        case 'string':
                            $value = (string) $value;
                            break;
                        default:
                            throw new \RuntimeException('Unknown casting type of ' . $settings['type']);
                            break;
                    }
        
                    $recordData[$columnName] = $value;
                }
                $record = $baseRecord;
                $record['record'] = $recordData;
                $output .= json_encode($record) . PHP_EOL;
                $state['value'][$stream['stream']]++;
            }
            $output .= json_encode($state) . PHP_EOL;
        }

        return $output;
    }
}
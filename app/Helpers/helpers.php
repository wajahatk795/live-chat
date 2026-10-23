<?php

function get_pusher_instance(): ?\Pusher\Pusher
{
    static $pusher = null;
    if ($pusher !== null) {
        return $pusher;
    }

    $key = config('broadcasting.connections.pusher.key') ?: env('PUSHER_APP_KEY');
    $secret = config('broadcasting.connections.pusher.secret') ?: env('PUSHER_APP_SECRET');
    $appId = config('broadcasting.connections.pusher.app_id') ?: env('PUSHER_APP_ID');
    $cluster = config('broadcasting.connections.pusher.options.cluster') ?: env('PUSHER_APP_CLUSTER', 'ap1');

    if (!$key || !$secret || !$appId) {
        return null;
    }

    try {
        $pusher = new \Pusher\Pusher(
            $key,
            $secret,
            $appId,
            [
                'cluster' => $cluster,
                'useTLS' => true,
                'timeout' => 3, // Fast timeout ensures requests never hang
            ]
        );
        return $pusher;
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('Pusher Client Initialization Error: ' . $e->getMessage());
        return null;
    }
}

function emit_pusher_notification($channel, $event, $data): bool
{
    try {
        $pusher = get_pusher_instance();
        if (!$pusher) {
            return false;
        }

        $pusher->trigger(pusher_channel_name($channel), $event, $data);
        return true;
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('Pusher Notification Error: ' . $e->getMessage());
        return false;
    }
}

function emit_pusher_batch(array $events): bool
{
    if (empty($events)) {
        return true;
    }

    try {
        $pusher = get_pusher_instance();
        if (!$pusher) {
            return false;
        }

        $events = array_map(static function (array $event): array {
            $event['channel'] = pusher_channel_name($event['channel']);
            return $event;
        }, $events);

        $pusher->triggerBatch($events);
        return true;
    } catch (\Throwable $e) {
        // Fallback to sequential triggers if triggerBatch encounters an error
        \Illuminate\Support\Facades\Log::warning('Pusher Batch Warning, falling back to sequential: ' . $e->getMessage());
        $success = true;
        foreach ($events as $ev) {
            try {
                $pusher->trigger(pusher_channel_name($ev['channel']), $ev['name'], $ev['data']);
            } catch (\Throwable $ex) {
                $success = false;
            }
        }
        return $success;
    }
}

/** Add an environment namespace so local and live deployments never share channels. */
function pusher_channel_name(string $channel): string
{
    $prefix = pusher_channel_prefix();

    return $prefix === '' ? $channel : $prefix . '.' . $channel;
}

function pusher_channel_prefix(): string
{
    $environment = preg_replace('/[^a-zA-Z0-9_-]/', '-', (string) config('app.env', 'production'));

    $prefix = config('broadcasting.connections.pusher.channel_prefix', $environment);

    return trim((string) $prefix, '-_');
}

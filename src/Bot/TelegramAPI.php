<?php

namespace Bot;

use CURLFile;

class TelegramAPI
{
    private static function apiRequest($method, $parameters = [])
    {
        $url = 'https://api.telegram.org/bot' . BOT_TOKEN . '/' . $method;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result, true);
    }

    public static function sendMessage($chat_id, $text, $keyboard = null)
    {
        $params = [
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ];
        if ($keyboard) {
            $params['reply_markup'] = json_encode($keyboard);
        }
        return self::apiRequest('sendMessage', $params);
    }

    public static function forwardMessage($chat_id, $from_chat_id, $message_id)
    {
        return self::apiRequest('forwardMessage', [
            'chat_id' => $chat_id,
            'from_chat_id' => $from_chat_id,
            'message_id' => $message_id,
        ]);
    }

    public static function editMessageCaption($chat_id, $message_id, $caption, $keyboard = null)
    {
        $params = [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'caption' => $caption,
            'parse_mode' => 'Markdown',
        ];
        if ($keyboard) {
            $params['reply_markup'] = json_encode($keyboard);
        }
        return self::apiRequest('editMessageCaption', $params);
    }

    public static function editMessageReplyMarkup($chat_id, $message_id, $keyboard = null)
    {
        $params = [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
        ];
        if ($keyboard) {
            $params['reply_markup'] = json_encode($keyboard);
        } else {
            $params['reply_markup'] = json_encode((object)[]); // To remove keyboard
        }
        return self::apiRequest('editMessageReplyMarkup', $params);
    }

    public static function sendPhoto($chat_id, $file_id, $caption = null, $keyboard = null)
    {
        $params = ['chat_id' => $chat_id, 'photo' => $file_id];
        if ($caption) {
            $params['caption'] = $caption;
        }
        if ($keyboard) {
            $params['reply_markup'] = json_encode($keyboard);
        }
        return self::apiRequest('sendPhoto', $params);
    }

    public static function sendVideo($chat_id, $file_id, $caption = null, $keyboard = null)
    {
        $params = ['chat_id' => $chat_id, 'video' => $file_id];
        if ($caption) {
            $params['caption'] = $caption;
        }
        if ($keyboard) {
            $params['reply_markup'] = json_encode($keyboard);
        }
        return self::apiRequest('sendVideo', $params);
    }

    public static function sendDocument($chat_id, $file_id, $caption = null, $keyboard = null)
    {
        $params = ['chat_id' => $chat_id, 'document' => $file_id];
        if ($caption) {
            $params['caption'] = $caption;
        }
        if ($keyboard) {
            $params['reply_markup'] = json_encode($keyboard);
        }
        return self::apiRequest('sendDocument', $params);
    }

    public static function answerCallbackQuery($callback_query_id, $text = '', $show_alert = false)
    {
        return self::apiRequest('answerCallbackQuery', [
            'callback_query_id' => $callback_query_id,
            'text' => $text,
            'show_alert' => $show_alert,
        ]);
    }

    public static function getFilePath($file_id)
    {
        $response = self::apiRequest('getFile', ['file_id' => $file_id]);
        if ($response && $response['ok']) {
            return $response['result']['file_path'];
        }
        return null;
    }

    public static function downloadFile($file_path, $destination)
    {
        $file_url = 'https://api.telegram.org/file/bot' . BOT_TOKEN . '/' . $file_path;
        $content = file_get_contents($file_url);
        if ($content !== false) {
            file_put_contents($destination, $content);
            return true;
        }
        return false;
    }

    public static function blurAndReuploadImage($file_id)
    {
        $file_path = self::getFilePath($file_id);
        if (!$file_path) {
            return null;
        }

        $tmp_dir = __DIR__ . '/../tmp/';
        if (!is_dir($tmp_dir)) {
            mkdir($tmp_dir, 0777, true);
        }

        $original_file = $tmp_dir . basename($file_path);
        if (!self::downloadFile($file_path, $original_file)) {
            return null;
        }

        // Use GD library to blur the image
        $image = imagecreatefromjpeg($original_file);
        if ($image) {
            for ($i = 0; $i < 50; $i++) { // Apply blur filter multiple times for stronger effect
                imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
            }

            $blurred_file = $tmp_dir . 'blurred_' . basename($file_path);
            imagejpeg($image, $blurred_file);
            imagedestroy($image);

            // Re-upload the blurred image to get a new file_id
            $response = self::apiRequest('sendPhoto', [
                'chat_id' => EDITOR_CHANNEL_ID, // Use a channel to "store" the file
                'photo'   => new CURLFile(realpath($blurred_file))
            ]);

            // Cleanup local files
            unlink($original_file);
            unlink($blurred_file);

            if ($response && $response['ok']) {
                // Return the file_id of the largest photo
                return end($response['result']['photo'])['file_id'];
            }
        }

        // Cleanup if image processing failed
        if (file_exists($original_file)) {
            unlink($original_file);
        }

        return null;
    }

    public static function sendMediaToEditor($message_data, $caption, $keyboard)
    {
        $params = [
            'chat_id' => EDITOR_CHANNEL_ID,
            'caption' => $caption,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode($keyboard)
        ];

        $method = '';
        switch ($message_data['type']) {
            case 'photo':
                $method = 'sendPhoto';
                $params['photo'] = $message_data['content'];
                break;
            case 'video':
                $method = 'sendVideo';
                $params['video'] = $message_data['content'];
                break;
            case 'document':
                $method = 'sendDocument';
                $params['document'] = $message_data['content'];
                break;
        }

        if ($method) {
            // Note: apiRequest is private, so we call it with self::
            return self::apiRequest($method, $params);
        }
        return null;
    }

    public static function editMessageText($chat_id, $message_id, $text, $keyboard = null)
    {
        $params = [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ];
        if ($keyboard) {
            $params['reply_markup'] = json_encode($keyboard);
        }
        return self::apiRequest('editMessageText', $params);
    }
}

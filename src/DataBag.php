<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\HttpServer;

use Hyperf\Codec\Json;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\Arrayable;
use Hyperf\Contract\Jsonable;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Stringable;

/**
 * 数据包
 * @author Verdient。
 */
class DataBag implements Jsonable
{
    /**
     * @param mixed $data 数据
     * @param string $message 提示信息
     * @param int $code 状态码
     * @author Verdient。
     */
    public function __construct(public $data = null, public $message = '', public $code = 200)
    {
    }

    /**
     * 创建成功数据包
     * @param mixed $data 数据
     * @param string $message 提示信息
     * @param int $code 状态码
     * @return static
     * @author Verdient。
     */
    public static function succeed($data = null, $message = 'Success', $code = 200)
    {
        return new static($data, $message, $code);
    }

    /**
     * 创建失败数据包
     * @param string $message 提示信息
     * @param int $code 状态码
     * @param mixed $data 数据
     * @return static
     * @author Verdient。
     */
    public static function failed($message = 'Success', $code = 400, $data = null)
    {
        /** @var ResponseInterface */
        $response = ApplicationContext::getContainer()->get(ResponseInterface::class);
        return $response->json([
            'code' => $code,
            'data' => $data,
            'message' => $message,
        ])->withStatus($code);
    }

    /**
     * 创建成功数据包
     * @param string $message 提示信息
     * @param mixed $data 数据
     * @param int $code 状态码
     * @return static
     * @author Verdient。
     */
    public static function message($message, $data = null, $code = 200)
    {
        return new static($data, $message, $code);
    }

    /**
     * 格式化数据
     * @param mixed $data 待格式化的数据
     * @return mixed
     * @author Verdient。
     */
    protected static function normalize($data)
    {
        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        }
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = static::normalize($value);
            }
        } else if (is_int($data)) {
            if ($data > 2147483647) {
                $data = (string) $data;
            }
        }
        return $data;
    }

    /**
     * @inheritdoc
     * @author Verdient。
     */
    public function __toString(): string
    {
        $data = $this->data;
        if (is_object($data)) {
            if ($data instanceof Arrayable) {
                $data = $this->data->toArray();
            } else if ($data instanceof Jsonable) {
                $data = (string) $this->data;
            } else if ($data instanceof Stringable) {
                $data = (string) $this->data;
            }
        }
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = static::normalize($value);
            }
        } else if (is_int($data)) {
            if ($data > 2147483647) {
                $data = (string) $data;
            }
        }
        return Json::encode([
            'code' => $this->code,
            'data' => $data,
            'message' => $this->message,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\HttpServer;

use Hyperf\Contract\Arrayable;
use Override;
use Stringable;
use UnitEnum;
use Verdient\Hyperf3\Enum\LabelManager;

/**
 * 数据包
 *
 * @author Verdient。
 */
class DataBag implements Arrayable
{
    /**
     * @param mixed $data 数据
     * @param string|int|float|Stringable|UnitEnum $message 提示信息
     * @param int $code 状态码
     * @param bool $isFailed 是否是错误
     *
     * @author Verdient。
     */
    protected function __construct(
        public readonly mixed $data,
        public readonly string|int|float|Stringable|UnitEnum $message,
        public readonly int $code,
        public readonly bool $isFailed = false
    ) {}

    /**
     * 创建成功数据包
     *
     * @param mixed $data 数据
     * @param string|int|float|Stringable $message 提示信息
     * @param int $code 状态码
     *
     * @author Verdient。
     */
    public static function succeed(
        mixed $data = null,
        string|int|float|Stringable $message = 'OK',
        int $code = 200
    ): static {
        return new static(data: $data, message: $message, code: $code);
    }

    /**
     * 创建失败数据包
     *
     * @param string|int|float|Stringable $message 提示信息
     * @param int $code 状态码
     * @param mixed $data 数据
     *
     * @author Verdient。
     */
    public static function failed(
        string|int|float|Stringable|UnitEnum $message,
        int $code = 400,
        mixed $data = null
    ): static {
        return new static(data: $data, message: $message, code: $code, isFailed: true);
    }

    /**
     * 创建消息数据包
     *
     * @param string $message 提示信息
     * @param mixed $data 数据
     * @param int $code 状态码
     *
     * @author Verdient。
     */
    public static function message(
        string|int|float|Stringable|UnitEnum $message,
        mixed $data = null,
        int $code = 200,
    ): static {
        return new static(data: $data, message: $message, code: $code);
    }

    /**
     * 格式化数据
     *
     * @param mixed $data 待格式化的数据
     *
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
     * @author Verdient。
     */
    #[Override]
    public function toArray(): array
    {
        $data = $this->data;

        if (is_object($data)) {
            if ($data instanceof Arrayable) {
                $data = $this->data->toArray();
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

        if ($this->message instanceof UnitEnum) {
            if ($label = LabelManager::label($this->message)) {
                $message = $label;
            } else {
                $message = $this->message->name;
            }
        } else {
            $message = (string) $this->message;
        }

        return [
            'code' => $this->code,
            'data' => $data,
            'message' => $message,
        ];
    }
}

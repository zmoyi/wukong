<?php

namespace Wukong\Im;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class Client
{

    private array $config;
    private static ?Client $instance = null;

    /**
     * 构造方法
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    /**
     * @param string $url
     * @param array $data
     * @return Exception|GuzzleException|ResponseInterface
     * @throws Exception
     */
    private function post(string $url,array $data): Exception|GuzzleException|Response
    {
        $config = $this->getConfig();
        $http = Http::getInstance($config)->getClient();
        try {
            return $http->post(
                $url,[
                    'json' => $data
                ]
            );
        } catch (GuzzleException $e) {
            return $e;
        }
    }

    // get

    /**
     * @return Exception|GuzzleException|ResponseInterface|string
     * 获取数据
     * @throws Exception
     */
    public function get(string $url): Exception|string|GuzzleException|ResponseInterface
    {
        $config = $this->getConfig();
        $http = Http::getInstance($config)->getClient();
        try {
            return $http->get(
                $url,[
                    //设置请求头为json格式
                    'headers' => [
                        'Content-Type' => 'application/json', // 设置请求头为JSON
                    ],
                ]
            );
        } catch (GuzzleException $e) {
            return $e;
        }
    }

    //长连接地址获取
    //获取客户端连接WuKongIM的地址
    //
    //GET /route?uid=xxxx // uid 为用户 ID
    /**
     * @throws Exception
     */
    public function getRoute(string $uid): Exception|string|GuzzleException
    {
        $url = $this->getConfig().'/route?uid='.$uid;
        return $this->get($url);
    }

    //批量获取连接地址
    //获取一批用户的连接地址
    //
    //POST /route/batch
    /**
     * @param array $uids
     * @return Exception|string|GuzzleException
     * @throws Exception
     */
    public function getRouteBatch(array $uids): Exception|string|GuzzleException
    {
        $url = $this->getConfig().'/route/batch';
        return $this->post($url,$uids);
    }

    /**
     * 注册或登录
     * 将用户信息注册到WuKongIM，如果存在则更新
     *
     * POST /user/token
     *
     * 请求参数:
     *
     * json
     * {
     * "uid": "xxxx", // 通信的用户唯一ID，可以随机uuid （建议自己服务端的用户唯一uid） （WuKongIMSDK需要）
     * "token": "xxxxx", // 校验的token，随机uuid（建议使用自己服务端的用户的token）（WuKongIMSDK需要）
     * "device_flag": 0, // 设备标识  0.app 1.web （相同用户相同设备标记的主设备登录会互相踢，从设备将共存）
     * "device_level": 1 // 设备等级 0.为从设备 1.为主设备
     * }
     * @throws Exception
     */
    public function userToken(array $data): Exception|string|GuzzleException
    {
        $url = $this->getConfig().'/user/token';
        return $this->post($url,$data);
    }

    /**
     * 用户在线状态
     * 查询一批用户的在线状态。
     *
     * POST /user/onlinestatus
     *
     * 请求参数:
     *
     * json
     * [uid123,uid345,uid456...] // 需要查询在线状态的用户uid列表
     * @throws Exception
     */
    public function userOnlineStatus(array $uids): Exception|string|GuzzleException
    {
        $url = $this->getConfig().'/user/onlinestatus';
        return $this->post($url,$uids);
    }

    /**
     * 添加系统账号
     * 系统账号将有发送消息的全部权限，不受黑名单限制，无需在订阅列表里，比如“系统通知”，“客服”等这种类似账号可以设置系统账号
     *
     * POST /user/systemuids_add
     *
     * 请求参数:
     *
     * json
     * {
     * "uids": [uid123,uid345,uid456...] // 需要加入系统账号的用户uid集合列表
     * }
     * @throws Exception
     */
    public function userSystemUidsAdd(array $uids): Exception|string|GuzzleException
    {
        $url = $this->getConfig().'/user/systemuids_add';
        return $this->post($url,$uids);
    }

    /**
     * 移除系统账号
     * 将系统账号移除
     *
     * POST /user/systemuids_remove
     *
     * 请求参数:
     *
     * json
     * {
     * "uids": [uid123,uid345,uid456...] // 系统账号的用户uid集合列表
     * }
     * @throws Exception
     */
    public function userSystemUidsRemove(array $uids): Exception|string|GuzzleException
    {
        $url = $this->getConfig().'/user/systemuids_remove';
        return $this->post($url,$uids);
    }

    /**
     * 踢出用户的设备登录
     * 将用户的设备踢出登录，（可以实现类似微信的 app 可以踢出 pc 登录）
     *
     * POST /user/device_quit
     *
     * 请求参数:
     *
     * json
     * {
     * "uid": "xxxx", // 需要踢出的用户uid
     * "device_flag": 1 // 需要踢出的设备标记 -1: 当前用户下所有设备 0： 当前用户下的app 1： 当前用户下的web 2： 当前用户下的pc
     * }
     * @throws Exception
     */
    public function userDeviceQuit(array $data): Exception|string|GuzzleException
    {
        $url = $this->getConfig().'/user/device_quit';
        return $this->post($url,$data);
    }

    /**
     * 创建或更新频道
     * 创建一个频道，如果系统中存在则更新（个人与个人聊天不需要创建频道，系统将自动创建）
     *
     * POST /channel
     *
     * 请求参数:
     *
     * json
     * {
     * "channel_id": "xxxx", // 频道的唯一ID，如果是群聊频道，建议使用群聊ID
     * "channel_type": 2, // 频道的类型 1.个人频道 2.群聊频道（个人与个人聊天不需要创建频道，系统将自动创建）
     * "large": 0,   // 是否是超大群，0.否 1.是 （一般建议500成员以上设置为超大群，注意：超大群不会维护最近会话数据。）
     * "ban": 0, // 是否封禁此频道，0.否 1.是 （被封后 任何人都不能发消息，包括创建者）
     * "subscribers": [uid1,uid2,...], // 订阅者集合
     * }
     * @throws Exception
     */
    public function channel(array $data): Exception|string|GuzzleException
    {
        $url = $this->getConfig().'/channel';
        return $this->post($url,$data);
    }

    /**
     * 删除频道
     * 删除一个频道（注意：如果配置了datasource记得不要返回删除了频道的数据，要不然重启又会恢复回来）
     *
     * /channel/delete
     *
     * 请求参数:
     *
     * json
     * {
     * "channel_id": "xxxx", // 频道的唯一ID
     * "channel_type": 2 // 频道的类型 1.个人频道 2.群聊频道
     * }
     * @throws Exception
     */
    public function channelDelete(array $data): Exception|string|GuzzleException
    {
        $url = $this->getConfig().'/channel/delete';
        return $this->post($url,$data);
    }

    /**
     * 添加订阅者
     * 向一个已存在的频道内添加订阅者
     *
     * POST /channel/subscriber_add
     *
     * 请求参数:
     *
     * json
     * {
     * "channel_id": "xxxx", // 频道的唯一ID
     * "channel_type": 2, // 频道的类型 1.个人频道 2.群聊频道
     * "reset": 0,        // // 是否重置订阅者 （0.不重置 1.重置），选择重置，则删除旧的订阅者，选择不重置则保留旧的订阅者
     * "subscribers": [uid1,uid2,...], // 订阅者集合
     * "temp_subscriber": 0 // 是否为临时频道 0.否 1.是 临时频道的订阅者将在下次重启后自动删除
     * }
     * @throws Exception
     */
    public function channelSubscriberAdd(array $data): Exception|string|GuzzleException
    {
        $url = $this->getConfig().'/channel/subscriber_add';
        return $this->post($url,$data);
    }

    /**
     * 移除订阅者
     * 向一个已存在的频道内移除订阅者
     *
     * POST /channel/subscriber_remove
     *
     * 请求参数:
     *
     * json
     * {
     * "channel_id": "xxxx", // 频道的唯一ID
     * "channel_type": 2, // 频道的类型 1.个人频道 2.群聊频道
     * "subscribers": [uid1,uid2,...], // 订阅者集合
     * }
     */
    public function channelSubscriberRemove(array $data): Exception|string|GuzzleException
    {
        $url = $this->getConfig().'/channel/subscriber_remove';
        return $this->post($url,$data);
    }

    /**
     * 添加黑名单
     * 将某个用户添加到频道黑名单内，在频道黑名单内的用户将不能在此频道发送消息，可以通过此接口实现，群拉黑群成员的功能
     *
     * POST /channel/blacklist_add
     *
     * 请求参数:
     *
     * json
     * {
     * "channel_id": "xxxx", // 频道的唯一ID
     * "channel_type": 2, // 频道的类型 1.个人频道 2.群聊频道
     * "uids": [uid1,uid2,...], // 要拉黑的用户uid集合
     * }
     */
    public function channelBlacklistAdd(array $data): Exception|string|GuzzleException
    {
        $url = $this->getConfig().'/channel/blacklist_add';
        return $this->post($url,$data);
    }

    /**
     * 移除黑名单
     * POST /channel/blacklist_remove
     *
     * 请求参数:
     *
     * json
     * {
     * "channel_id": "xxxx", // 频道的唯一ID
     * "channel_type": 2, // 频道的类型 1.个人频道 2.群聊频道
     * "uids": [uid1,uid2,...], // 用户uid集合
     * }
     */
    public function channelBlacklistRemove(array $data): Exception|string|GuzzleException
    {
        $url = $this->getConfig().'/channel/blacklist_remove';
        return $this->post($url,$data);
    }

    /**
     * 设置黑名单
     * 设置黑名单（覆盖原来的黑名单数据）
     *
     * POST /channel/blacklist_set
     *
     * 请求参数:
     *
     * json
     * {
     * "channel_id": "xxxx", // 频道的唯一ID
     * "channel_type": 2, // 频道的类型 1.个人频道 2.群聊频道
     * "uids": [uid1,uid2,...], // 用户uid集合
     * }
     */
    public function channelBlacklistSet(array $data): Exception|string|GuzzleException
    {
        $url = $this->getConfig().'/channel/blacklist_set';
        return $this->post($url,$data);
    }

    /**
     * 添加白名单
     * 如果设置了白名单，则只允许白名单内的订阅者发送消息。可以通过白名单机制实现“群禁言功能”，也可以通过白名单实现只允许跟好友聊天的功能
     *
     * POST /channel/whitelist_add
     *
     * 请求参数:
     *
     * json
     * {
     * "channel_id": "xxxx", // 频道的唯一ID 个人频道则为用户的uid
     * "channel_type": 2, // 频道的类型 1.个人频道 2.群聊频道
     * "uids": [uid1,uid2,...], // 用户uid集合
     * }
     */
    public function channelWhitelistAdd(array $data): Exception|string|GuzzleException
    {
        $url = $this->getConfig().'/channel/whitelist_add';
        return $this->post($url,$data);
    }

    /**
     * 移除白名单
     * 将用户从频道白名单内移除
     *
     * /channel/whitelist_remove
     *
     * 请求参数:
     *
     * json
     * {
     * "channel_id": "xxxx", // 频道的唯一ID
     * "channel_type": 2, // 频道的类型 1.个人频道 2.群聊频道
     * "uids": [uid1,uid2,...], // 用户uid集合
     * }
     */
    public function channelWhitelistRemove(array $data): Exception|string|GuzzleException
    {
        $url = $this->getConfig().'/channel/whitelist_remove';
        return $this->post($url,$data);
    }

    /**
     * 设置白名单
     * 设置白名单（覆盖原来的白名单数据）
     *
     * POST /channel/whitelist_set
     *
     * 请求参数:
     *
     * json
     * {
     * "channel_id": "xxxx", // 频道的唯一ID
     * "channel_type": 2, // 频道的类型 1.个人频道 2.群聊频道
     * "uids": [uid1,uid2,...], // 用户uid集合
     * }
     */
    public function channelWhitelistSet(array $data): Exception|string|GuzzleException
    {
        $url = $this->getConfig().'/channel/whitelist_set';
        return $this->post($url,$data);
    }

    /**
     * 发送消息
     * 服务端调用发送消息接口可以主要用来发送系统类的消息，比如群成员进群通知，消息撤回通知等等
     *
     * POST /message/send
     *
     * 请求参数:
     *
     * json
     * {
     * "header": {
     * // 消息头
     * "no_persist": 0, // 是否不存储消息 0.存储 1.不存储
     * "red_dot": 1, // 是否显示红点计数，0.不显示 1.显示
     * "sync_once": 0 // 是否是写扩散，这里一般是0，只有cmd消息才是1
     * },
     * "from_uid": "xxxx", // 发送者uid
     * "stream_no": "", // 流式消息编号，如果是流式消息，需要指定，否则为空
     * "channel_id": "xxxx", // 接收频道ID 如果channel_type=1 channel_id为个人uid 如果channel_type=2 channel_id为群id
     * "channel_type": 2, // 接收频道类型  1.个人频道 2.群聊频道
     * "payload": "xxxxx", // 消息，base64编码，消息格式参考下面 【payload 内容参考】的链接
     * "subscribers": ["uid123", "uid234", "..."] // 订阅者 如果此字段有值，表示消息只发给指定的订阅者,没有值则发给频道内所有订阅者
     * }
     */
    public function messageSend(array $data): Exception|string|GuzzleException
    {
        $url = $this->getConfig().'/message/send';
        return $this->post($url,$data);
    }

    /**
     * 批量发送消息
     * 批量发送消息，可以用于后端发送全局通知之类的消息，需要通知到全部用户的消息，可以每次指定一批（通过 subscribers 指定）接收用户，分批推送。
     *
     * POST /message/sendbatch
     *
     * 请求参数:
     *
     * json
     * {
     * "header": { // 消息头
     * "no_persist": 0, // 是否不存储消息 0.存储 1.不存储
     * "red_dot": 1, // 是否显示红点计数，0.不显示 1.显示
     * "sync_once": 0, // 是否是写扩散，这里一般是0，只有cmd消息才是1
     * },
     * "from_uid": "xxxx", // 发送者uid
     * "payload": "xxxxx", // 消息内容，base64编码
     * "subscribers": [uid123,uid234,...] // 接收者的uid，分批指定，每次建议 1000-10000之间，视系统情况而定
     * }
     */
    public function messageSendBatch(array $data): Exception|string|GuzzleException
    {
        $url = $this->getConfig().'/message/sendbatch';
        return $this->post($url,$data);
    }

    /**
     * 获取某频道消息
     * 获取某个频道的消息列表
     *
     * POST /channel/messagesync
     *
     * 请求参数:
     *
     * json
     * {
     * "login_uid": "xxxx", // 当前登录用户uid
     * "channel_id": "xxxx", //  频道ID
     * "channel_type": 2, // 频道类型
     * "start_message_seq": 0, // 开始消息列号（结果包含start_message_seq的消息）
     * "end_message_seq": 0, // 结束消息列号（结果不包含end_message_seq的消息）
     * "limit": 100, // 消息数量限制
     * "pull_mode": 1 // 拉取模式 0:向下拉取 1:向上拉取
     * }
     */
    public function channelMessageSync(array $data): Exception|string|GuzzleException
    {
        $url = $this->getConfig().'/channel/messagesync';
        return $this->post($url,$data);
    }

    /**
     * 同步离线命令消息
     * 如果消息 header.sync_once 设置为 1 则离线命令消息就会走此接口，否则走读扩散模式（建议只有 CMD 消息才走写扩散）
     *
     * POST /message/sync
     *
     * 请求参数:
     *
     * json
     * {
     * "uid": "xxxx", // 当前登录用户uid
     * "limit": 100 //  消息数量限制
     * }
     */
    public function messageSync(array $data): Exception|string|GuzzleException
    {
        $url = $this->getConfig().'/message/sync';
        return $this->post($url,$data);
    }

    /**
     * 回执离线命令消息
     * 当客户端获取完离线命令消息后，需要调用此接口做回执，告诉服务端离线消息已获取完毕，这样下次就不会再返回
     *
     * POST /message/syncack
     *
     * 请求参数:
     *
     * json
     * {
     * "uid": "xxxx", // 当前登录用户uid
     * "last_message_seq": 0 //  客户端本地最后一条命令消息的messageSeq，如果本地没有命令消息则为0
     * }
     */
    public function messageSyncAck(array $data): Exception|string|GuzzleException
    {
        $url = $this->getConfig().'/message/syncack';
        return $this->post($url,$data);
    }

    /**
     * 同步最近会话
     * 客户端离线后每次进来需要同步一次最近会话（包含离线的最新的消息）
     *
     * POST /conversation/sync
     *
     * 请求参数:
     *
     * json
     * {
     * "uid": "xxxx", // 当前登录用户uid
     * "version": 1234, //  当前客户端的会话最大版本号(从保存的结果里取最大的version，如果本地没有数据则传0)，
     * "last_msg_seqs": "xxx:2:123|xxx:1:3434", //   客户端所有频道会话的最后一条消息序列号拼接出来的同步串 格式： channelID:channelType:last_msg_seq|channelID:channelType:last_msg_seq  （此字段非必填，如果不填就获取全量数据，填写了获取增量数据，看你自己的需求。）
     * "msg_count": 20 // 每个会话获取最大的消息数量，一般为app点进去第一屏的数据
     * }
     */
    public function conversationSync(array $data): Exception|string|GuzzleException
    {
        $url = $this->getConfig().'/conversation/sync';
        return $this->post($url,$data);
    }

    /**
     * 设置最近会话未读数量
     * 设置某个频道的最近会话未读消息数量
     *
     * POST /conversations/setUnread
     *
     * 请求参数:
     *
     * json
     * {
     * "uid": "xxxx", // 当前登录用户uid
     * "channel_id": "xxxx", // 频道ID
     * "channel_type": 1, // 频道类型
     * "unread": 0 // 未读消息数量
     * }
     */
    public function conversationSetUnread(array $data): Exception|string|GuzzleException
    {
        $url = $this->getConfig().'/conversations/setUnread';
        return $this->post($url,$data);
    }

    /**
     * 删除最近会话
     * 删除某个频道的最近会话
     *
     * POST /conversations/delete
     *
     * 请求参数:
     *
     * json
     * {
     * "uid": "xxxx", // 当前登录用户uid
     * "channel_id": "xxxx", // 频道ID
     * "channel_type": 1 // 频道类型
     * }
     */
    public function conversationDelete(array $data): Exception|string|GuzzleException
    {
        $url = $this->getConfig().'/conversations/delete';
        return $this->post($url,$data);
    }



    /**
     * @throws Exception
     */
    public function getConfig()
    {
        if ($this->config['apiUrl']){
            return $this->config['apiUrl'];
        }
        // 抛出错误
        throw new Exception('apiUrl is not set',0);
    }

    /**
     * @param array $config
     * @return void
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    /**
     * 获取实例
     */
    public static function getInstance(): Client
    {
        $config = config('plugin.wukong.im.init');

        if(self::$instance == null){
            self::$instance = new self($config);
        }
        return self::$instance;
    }

}
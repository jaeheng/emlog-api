# Emlog API设计文档

# 已过时，emlog pro已内置API，请以官方API为准
# 已过时，emlog pro已内置API，请以官方API为准
# 已过时，emlog pro已内置API，请以官方API为准
# 已过时，emlog pro已内置API，请以官方API为准

## 本API文档说明

有些Emlog模板的制作需要用到Ajax去异步调用数据，此时免不了要去写一些接口，模板制作的时候去写接口调用，无疑增加了开发者的工作量。于是我就想先规范一些Emlog的API接口的调用方式，然后制作一款API接口插件，这样可以减少模板开发者的技术负担，专注于界面的设计。

## Download

克隆或下载本项目，其中wxa文件夹就是小程序插件

## 使用方式
```
1. 将小程序插件目录放入网站的插件目录/content/plugins
2. Ajax访问 `BLOG_URL + 'content/plugins/wxa/api.php?route=请求地址&请求参数` 即可
3. 拼接地址大概如： https://blog.zhangziheng.com/content/plugins/wxa/api.php?route=article&sid=1
```

## 获取文章列表

- 请求方式: GET
- 请求地址: `article`
- 请求参数:
	- sid 分类ID 可选，无sid则获取所有分类最新的文章
	- page 页码
	- perpage 每页条数 默认从emlog配置中取
	- top 是否仅获取首页置顶文章
	- keyword 关键字
- 响应数据:
```js
{
	state: 1, // 1 获取成功 0 获取失败
	msg: '', // 提示信息
	data: {
		total: 100, // 总数
		list: [
			{
				gid: 1, // 文章ID
				title: '', // 文章标题
				date: 1527669094, // 发布时间，unix时间戳
				excerpt: '', // 描述
				author: 1, // 作者ID
				nickname: '', // 作者昵称
				sortid: -1, // 分类ID
				sortname: '未分类', // 分类名称
				views: 100, // 浏览数
				comnum: 100, // 评论数
				top: 'n', // 首页置顶 n 不置顶 y 置顶
				sortop: 'n', // 分类置顶 n 不置顶 y 置顶
			}
		]
	}
}
```

## 获取文章/页面详情

- 请求方式: GET
- 请求地址: `articleInfo`
- 请求参数:
	- gid = 1 文章id
- 响应数据:
```js
{
	state: 1, // 1 获取成功 0 获取失败
	msg: '', // 提示信息
	data: {
		gid: 1, // 文章id
		title: '', // 文章标题
		date: 1527669094, // 发布时间，unix时间戳
		content: '', // 内容
		sortid: 1, // 分类id
		sortname: 'xx', // 分类名称
		views: 100, // 浏览数
		comnum: 100, // 评论数
		author: 1, // 作者id
		nickname: '', // 作者昵称
		allow_remark: 'y', // 是否允许评论
		tags: [
			{
				tid: 1,
				tagname: ''
			}
		] // 标签
	}
}
```

## 获取某文章/页面关联的评论列表

- 请求方式: GET
- 请求地址: `comments`
- 请求参数:
	- gid 文章ID
	- page 评论页码
- 响应数据:
```js
{
	state: 1, // 1 获取成功 0 获取失败
	msg: '', // 提示信息
	data: {
		total: 100, // 评论总数
		list: [
			{
				cid: 1, // 评论ID
				date: 1527138590, // 评论时间
				poster: 'admin', // 评论者昵称
				comment: '评论内容', // 评论内容
				children: [ // 子评论
					{
						cid: 1, // 评论ID
						date: 1527138590, // 评论时间
						poster: 'admin', // 评论者昵称
						comment: '评论内容', // 评论内容
						children: []
					}
				]
			}
		]
	}
}
```

## 发表评论

- 请求方式: POST
- 请求地址: `addComments`
- 请求参数:
	- gid 文章ID
	- poster 评论者昵称
	- mail 评论者邮箱 选填
	- url 评论人个人主页 选填
	- comment 评论内容
	- imgcode 验证码
- 响应数据:
```js
{
	state: 1, // 1 获取成功 0 获取失败
	msg: '', // 提示信息
	data: {
		total: 100, // 评论总数
		list: [
			{
				cid: 1, // 评论ID
				date: 1527138590, // 评论时间
				poster: 'admin', // 评论者昵称
				comment: '评论内容', // 评论内容
				children: [ // 子评论
					{
						cid: 1, // 评论ID
						date: 1527138590, // 评论时间
						poster: 'admin', // 评论者昵称
						comment: '评论内容', // 评论内容
						children: []
					}
				]
			}
		]
	}
}
```

## 验证码

- 地址： http://BLOG_URL/include/lib/checkcode.php
- 当成图片引入显示就行
- 如： `<img src="http://BLOG_URL/include/lib/checkcode.php" />`

## 获取最新评论
> 从所有评论离获取几条最新的

- 请求方式: GET
- 请求地址: `newComments`
- 请求参数: -
- 说明: 条数可在后台侧边栏设置：最新评论
- 响应数据:
```js
{
	state: 1, // 1 获取成功 0 获取失败
	msg: '', // 提示信息
	data:  [
		{
			cid: 1, // 评论ID
			gid: 1,
			date: 1527138590, // 评论时间
			name: 'admin', // 评论者昵称
			content: '评论内容' // 评论内容
		}
	]
}
```

## 获取最新微语

- 请求方式: GET
- 请求地址: `twitter`
- 请求参数:
	- page 页码
- 响应数据:
```js
{
	state: 1, // 1 获取成功 0 获取失败
	msg: '', // 提示信息
	data:  {
		total: 100, // 总数
		list: [
			{
				id: 1, // 微语ID
				content: '', // 微语内容
				img: '', // 微语包含的图片地址
				author: 1, // 微语作者ID
				nickname: '', // 微语作者昵称
				date: '2013-04-04 10:58', // 发布时间
				replynum: 5, // 几条回复
				t: '' // 替换表情处理过的内容
			}
		]
	}
}
```

## 获取微语回复

- 请求方式: GET
- 请求地址: `replyTwitter`
- 请求参数:
	- tid 微语ID
- 响应数据:
```js
{
	state: 1, // 1 获取成功 0 获取失败
	msg: '', // 提示信息
	data:  [
		{
			id: 1, // 回复ID
			content: '', // 回复内容
			name: '', // 回复者昵称
			date: '2018-06-21 16:02' // 回复时间
		}
	]
}
```

## 获取分类列表

- 请求方式: GET
- 请求地址: `sorts`
- 请求参数: - 
- 响应数据:
```js
{
	state: 1, // 1 获取成功 0 获取失败
	msg: '', // 提示信息
	data:  [
		{
			sid: 1, // 分类ID
			sortname: '', // 分类名称
			alias: '', // 分类别名
			description: '', // 描述
			template: '', // 分类模版
			lognum: 15, // 包含几篇文章
			taxis: 5, // 排序序号
			pid: 0, // 父类id
			children: [ // 子分类
				{
					sid: 1, // 分类ID
					sortname: '', // 分类名称
					alias: '', // 分类别名
					description: '', // 描述
					template: '', // 分类模版
					lognum: 15, // 包含几篇文章
					taxis: 5, // 排序序号
					pid: 0 // 父类id
				}
			]
		}
	]
}
```

## 获取文章附件

- 请求方式: GET
- 请求地址: `attachment`
- 请求参数:
	- blogid 文章ID
	- thumb 是否获取缩略图 1 获取 0 不获取
- 响应数据:
```js
{
	state: 1, // 1 获取成功 0 获取失败
	msg: '', // 提示信息
	data:  [
		{
			aid: 1, // 附件ID
			filename: '', // 文件名称（上传之前的名称）
			filesize: 100, // 文件大小（Byte）
			filepath: '', // 存储路径
			addtime: 1502337380, // 上传时间
			thumfor: 0, // 是谁的缩略图
			mimetype: 'image/png', // memetype
			height: 397, // 图片的真实高度, （非图片为0）
			width: 579 // 图片的真实宽度，（非图片为0）
		}
	]
}
```

## 获取系统设置
> 仅输出部分公开的配置

- 请求方式: GET
- 请求地址: `options`
- 请求参数: - 
- 响应数据:
```js
{
	state: 1, // 1 获取成功 0 获取失败
	msg: '', // 提示信息
	data:  {
		blogname: '子恒博客', // 博客名称
		bloginfo: '', // 博客简介
		site_key: '', // 博客关键字
		blogurl: '', // 博客链接
		icp: '', // icp备案号
		site_title: '', // SEO网站标题
		site_description: '' // SEO网站描述
	}
}
```

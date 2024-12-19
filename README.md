# Laravel NATS PHP Sender

# TODO

| Done ?               | Name                                                                 | Version       |
|:---------------------|:---------------------------------------------------------------------|:--------------|
| :white_large_square: | Add command `nats:pause` and `nats:continue`.                        | In the future |
| :white_large_square: | Writing documentation.                                               | In the future |
| :white_large_square: | Add multiple ways to retrieve information.(`sync`, `redis` or other) | In the future |

# Install

```
composer require akbarali/nats-sender
```

Supervisor add Artisan command:

```aiignore
php artisan nats:redis:sender
```

# Supervisor Configuration

Supervisor configuration files are typically stored within your server's `/etc/supervisor/conf.d` directory.
Within this directory, you may create any number of configuration files that instruct supervisor how your processes should be monitored.
For example, let's create a `nats_sender.conf` file that starts and monitors a nats listener process:

```
[program:nats_sender]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/artisan nats:redis:sender
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=1
user=forge
redirect_stderr=true
stdout_logfile=/var/www/supervisor/nats_sender_queue.log
```

# Emotional Damage

I originally wrote this package because RabbitMQ was slow and had a lot of issues with our entire project.
I thought NATS would be faster and more reliable.
But in the end, it turned out that RabbitMQ is 25% faster.

![alt text](/art/emotional-damage.gif)
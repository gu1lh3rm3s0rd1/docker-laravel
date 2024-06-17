# Subindo uma aplicação Laravel com container Docker

1. Clone o repositório: [https://github.com/gu1lh3rm3s0rd1/events-laravel.git](https://github.com/gu1lh3rm3s0rd1/events-laravel.git)
   Acesse a raiz do projeto:
   O primeiro passo é copiar o arquivo `.env.example` para `.env`, pois o Laravel usa o arquivo `.env` para carregar as variáveis de ambiente. O arquivo `.env.example` é um exemplo de como o arquivo `.env` deve ser configurado.

```bash
    cp .env.example .env
```

2. Em seguida, vamos criar os containers utilizando builds prontas para este projeto. Antes disso, precisamos preparar nosso ambiente:
   - Crie um arquivo chamado `default.conf` no caminho `./docker/nginx/default.conf` e adicione o seguinte conteúdo:
     Neste arquivo, estamos configurando o servidor Nginx para ouvir na porta 80, configurando o root do servidor para a pasta `/var/www/public` (onde o Laravel armazena os arquivos públicos) e redirecionando todas as requisições para o arquivo `index.php`.

```nginx
    server {
        listen 80;
        index index.php;
        root /var/www/public;

        add_header X-Frame-Options "SAMEORIGIN";
        add_header X-Content-Type-Options "nosniff";

        charset utf-8;

        location / {
            try_files $uri $uri/ /index.php?$query_string;
        }

        location = /favicon.ico { access_log off; log_not_found off; }
        location = /robots.txt  { access_log off; log_not_found off; }

        error_page 404 /index.php;

        location ~ \.php$ {
            fastcgi_pass app-laravel:9000;
            fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
            include fastcgi_params;
        }

        location ~ /\.(?!well-known).* {
            deny all;
        }
    }
```

3. Crie um arquivo chamado `docker-compose.yml` na raiz do projeto:
   
```yaml
    services:
        app:
            image: gu1lh3rm3s0rd1/laravel
            container_name: app-laravel
            tty: true
            ports:
                - "9000:9000"
            volumes:
                - .:/var/www
            networks:
                - laravel
            depends_on:
                - db

        web:
            image: nginx:alpine
            container_name: nginx
            ports:
                - "80:80"
            volumes:
                - .:/var/www
                # - ./docker/nginx.conf:/etc/nginx/conf.d/default.conf
                - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
            networks:
                - laravel

        db:
            image: mysql:5.7
            container_name: mysql-db
            environment:
                MYSQL_DATABASE: hdc_events
                MYSQL_ROOT_PASSWORD: root
                MYSQL_USER: dockeruser
                MYSQL_PASSWORD: dockersenha
            ports:
                - "3307:3306"
            volumes:
                - dbdata:/var/lib/mysql
            networks:
                - laravel

    networks:
        laravel:
            driver: bridge

    volumes:
        dbdata:
```

   Neste arquivo, estamos configurando três serviços: a aplicação (chamada de `app`), o Nginx (chamado de `web`, para servir e redirecionar as requisições web para a aplicação) e o banco de dados (chamado de `db`, para armazenar e consumir dados posteriormente).

1. Antes de executar o arquivo, altere as configurações de conexão do banco de dados no arquivo `.env`:
  
```env
   # docker-container
    DB_CONNECTION=mysql
    DB_HOST=db
    DB_PORT=3306
    DB_DATABASE=hdc_events
    DB_USERNAME=dockeruser
    DB_PASSWORD=dockersenha
```

5. Por fim, inicie os containers:
   
```bash
    docker-compose up -d --build
```

6. Em seguida, acesse o container do app-laravel:
   
```bash
    winpty docker exec -it (container_id ou nome) bash  # se estiver usando um terminal Git
    docker exec -it (container_id ou nome) bash
    docker exec -it (container_id ou nome) sh  # se bash não estiver disponível
```

   Em alguns casos, o shell padrão do container pode ser `sh` em vez de `bash`. Se você enfrentar problemas ao usar o comando `bash`, tente usar o comando `sh` para acessar o shell do container.

8. Execute os seguintes comandos dentro do container:
    
```bash
    composer install  # instala as dependências do Composer para a aplicação
    npm install  # instala as dependências do Node
    chown -R www-data:www-data storage  # define as permissões da pasta storage para o Nginx
    php artisan key:generate  # cria a chave criptografada de acesso para a aplicação
    php artisan migrate  # migra as tabelas para o banco de dados
```

Seguindo esses passos, conseguiremos subir nossa aplicação Laravel com container. Ao acessar `localhost:80`, veremos nossa aplicação rodando.

Caso enfrente algum erro, tente rodar dentro do container:

```bash
    composer require laravel/jetstream
    php artisan jetstream:install livewire
```

Posteriormente, acesse o model de usuários em `app/Models/User.php` e no final do arquivo insira esse trecho de cófigo caso ele nao esteja lá:

```php
    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function eventsAsParticipant()
    {
        return $this->belongsToMany(Event::class, 'event_user', 'user_id', 'event_id');
    }
```

Se ainda estiver enfrentando problemas no seu ambiente, tente limpar o cache e recriar os containers:

```bash
    docker system prune -a  # limpa o contexto de build e o cache do Docker
```

E pronto, agora você tem um ambiente Laravel rodando em containers Docker com PHP e Nginx.

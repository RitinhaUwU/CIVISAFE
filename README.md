# Dev Environment

Backend:
```shell
php artisan serve
php artisan reverb:start
php artisan queue:work
php artisan queue:work --queue=notifications
php artisan queue:work --queue=imports
```

Frontend:
```shell
npm run dev
```

# S3-Compatible Storage (S3/MinIO/RustFS)

Devem ser configurados os seguintes buckets e permissões no S3:

 - `misc`
   - Acesso `write` anónimo em `/` 


# Filas

- Default: Fila padrão do Laravel
- imports: fila onde são importados ficheiros e afins
- notifications: Fila de lançamento de notificações. Estão separadas para não serem bloqueadas por outros processos


# Variáveis de Ambiente

> ATENÇÃO! Todas as URLs/hosts nos ficheiros de configuração devem ser indicados SEM a barra (`/`) final

### Frontend

- `NUXT_PUBLIC_API_BASE`: URL base da api. Exemplo: `https://api.example.com/api/v1`
- `NUXT_PUBLIC_REVERB_APP_KEY`: Chave do reverb, tal como indicado na variável de ambiente `REVERB_APP_KEY` do backend
- `NUXT_PUBLIC_REVERB_HOST`: URL do servidor _Reverb_. Exemplo: `https://ws.example.com`
- `NUXT_PUBLIC_REVERB_PORT`: Porta onde o servidor _Reverb_ está a ser executado.
- `NUXT_PUBLIC_REVERB_SCHEME`: `http`/`https`. Esta variável só é usada para forçar TLS caso seja `https`.

### Backend

- `APP_ENV`: Ambiente de execução. Deve ser colocado como `production` quando em produção.
- `APP_KEY`: Chave a ser gerada com o comando `php artisan key:generate`.
- `APP_DEBUG`: Modo de depuração da aplicação. Deve ser colocado como `false` quando em produção.
- `DB_CONNECTION`: Base de dados a ser usada. Deve ser `pgsql` para o PostgreSQL.
- `DB_HOST`: Hostname/IP do servidor de base de dados
- `DB_PORT`: Porta do servidor, `5432` é o default para PostgreSQL.
- `DB_DATABASE`: Database/Schema no servidor de base de dados.
- `DB_USERNAME`: User com permissões para aceder à base de dados
- `DB_PASSWORD`: Password associada ao utilizador
- `SESSION_DRIVER`: Deverá ser definido como `redis` para utilização distribuída
- `BROADCAST_CONNECTION`: Deve ser definido como `reverb`.
- `QUEUE_CONNECTION`: Deve ser definido como `redis`.
- `CACHE_STORE`: Deve ser definido como `redis`.
- `REDIS_CLIENT`: Deve ser definido como `predis`.
- `REDIS_HOST`: Hostname/IP do servidor _Redis_.
- `REDIS_PASSWORD`: Password do servidor _Redis_.
- `AWS_ENDPOINT`: URL do servidor de buckets (Não da consola de gestão). Exemplo: `https://storage.example.com`.
- `AWS_ACCESS_KEY_ID`: Chave de acesso/Username da conta de acesso ao servidor de buckets.
- `AWS_SECRET_ACCESS_KEY`: Chave secreta/Password da conta de acesso ao servidor de buckets.
- `AWS_DEFAULT_REGION`: Região padrão dos buckets na AWS. S3-Compatible normalmente usa `us-east-1` como padrão.
- `AWS_USE_PATH_STYLE_ENDPOINT`: Deve ser definido como `true`.
- `REVERB_SCALING_ENABLED`: Deve ser colocado como `true` para utilização em ambiente distribuído.
- `REVERB_APP_ID`: ID da aplicação. Um inteiro que identifique a aplicação em caso de existirem várias no servidor _Reverb_.
- `REVERB_APP_KEY`: Uma chave partilhada com o frontend utilizada pelo _Reverb_.
- `REVERB_APP_SECRET`: Um segredo utilizado pelo _Reverb_.
- `REVERB_HOST`: URL do servidor _Reverb_. Exemplo: `https://ws.example.com`
- `REVERB_PORT`: Porta onde o servidor _Reverb_ está a ser executado.
- `REVERB_SCHEME`: `http`/`https`. Esta variável só é usada para forçar TLS caso seja `https`.

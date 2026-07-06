import {openDB, type IDBPDatabase} from 'idb'
import type {QueryParams} from '@/types'
import { useRuntimeConfig } from '#imports'

let database: IDBPDatabase<unknown> | null = null;

export async function createDB() {

  if (database !== null) {
    return;
  }

  if (navigator.storage && navigator.storage.persist) {
    const res = await navigator.storage.persist();
    console.debug(`Estado de persistência de dados: ${res}`);
  }

  // Fonte: https://web.dev/learn/pwa/offline-data#creating_and_opening_a_database
  // Using https://github.com/jakearchibald/idb

  /**
   * TODO: Incrementar o valor de da versão à medida que se fazem atualizações à estrutura
   * A version especificada abaixo openDB('civisafe', <version>) é o a versão atual da database.
   * Este valor é sempre i+1 do que os que estão nos cases do switch de oldversion.
   * O fallthrough dos switches é proposital para instalar tudo a partir da oldversion especificada.
   * Por padrão, um utilizador que nunca entrou no site tem a versão 0 da database. Não é possível atribuir
   * o valor 0 à versão da database.
   *
   * Ou seja, se estou a fazer a versão 2 da database, tenho de editar o valor da versão para 2 e escrever o
   * case 1 do switch. Quando se fizer a 3 altera-se o valor da versão para 3 e cria-se o case 2 e assim sucessivamente
   */
  database = await openDB('civisafe', 2, {
    upgrade(db, oldVersion, newVersion, transaction) {
      console.debug("[IDB] Versão Instalada: ", oldVersion);
      console.debug("[IDB] Versão mais recente: ", newVersion);
      switch (oldVersion) {
        case 0: {
          const userStore = db.createObjectStore('users', {keyPath: 'id'});
          userStore.createIndex('name', 'name', {unique: false});

          const incidentStatesStore = db.createObjectStore('incidentStates', {keyPath: 'id'});
          incidentStatesStore.createIndex('name', 'name', {unique: false});

          const incidentPrioritiesStore = db.createObjectStore('incidentPriorities', {keyPath: 'id'});
          incidentPrioritiesStore.createIndex('name', 'name', {unique: false});
          incidentPrioritiesStore.createIndex('description', 'description', {unique: false});

          const incidentTypesStore = db.createObjectStore('incidentTypes', {keyPath: 'id'});
          incidentTypesStore.createIndex('code', 'code', {unique: true});
          incidentTypesStore.createIndex('species', 'species', {unique: false});
          incidentTypesStore.createIndex('type', 'type', {unique: false});

          db.createObjectStore('incidents', {keyPath: 'id'});

          const entityTypesStore = db.createObjectStore('entityTypes', {keyPath: 'id'});
          entityTypesStore.createIndex('name', 'name', {unique: false});

          const entitiesStore = db.createObjectStore('entities', {keyPath: 'id'});
          entitiesStore.createIndex('name', 'name', {unique: false});

          const volunteersStore = db.createObjectStore('volunteers', {keyPath: 'id'});
          volunteersStore.createIndex('name', 'name', {unique: false});
          volunteersStore.createIndex('team_identification', 'team_identification', {unique: false});
          volunteersStore.createIndex('classification', 'classification', {unique: false});
          volunteersStore.createIndex('has_accommodation', 'has_accommodation', {unique: false});
          volunteersStore.createIndex('has_meal', 'has_meal', {unique: false});

          const facilitiesStore = db.createObjectStore('facilities', {keyPath: 'id'});
          facilitiesStore.createIndex('name', 'name', {unique: false});

          const donationGoodsTypes = db.createObjectStore('donation_goods_types', {keyPath: 'id'});
          donationGoodsTypes.createIndex('name', 'name', {unique: false});

          const donationLogs = db.createObjectStore('donation_logs', {keyPath: 'id'});
          donationLogs.createIndex('name', 'name', {unique: false});
          donationLogs.createIndex('donor_type', 'donor_type', {unique: false});
        }

        case 1: {
          console.log("Executing case 1")
          const incident_pcos = db.createObjectStore('incident_pcos', {keyPath: 'id'});
          incident_pcos.createIndex('incident_id', 'incident_id', {unique: false});

          const incident_parties = db.createObjectStore('incident_parties', {keyPath: 'id'});
          incident_parties.createIndex('incident_id', 'incident_id', {unique: false});

          const incident_timeline = db.createObjectStore('incident_timeline');
          incident_timeline.createIndex('log_id', 'log_id', {unique: true});
          incident_timeline.createIndex('comment_id', 'comment_id', {unique: true});
        }
      }
    }
  });
}

export async function storeData(objectStore: string, object: object) {
  if (database === null) {
    await createDB()
  }

  const tx = database?.transaction(objectStore, 'readwrite');

  if (tx === null || tx === undefined) {
    throw new Error(`Erro ao iniciar a transação readwrite para "${objectStore}"`);
  }

  const store = tx.objectStore(objectStore);

  if (Array.isArray(object)) {
    for (const item of object) {
      await store.put(item);
    }
  } else {
    await store.put(object);
  }

  await tx.done;
}

export async function retrieveData(objectStore: string, index: number = -1, params?: QueryParams) {
  if (typeof index !== 'number') {
    throw new Error("Typeof do index inválido: " + typeof index);
  }

  if (database === null) {
    await createDB()
  }

  const tx = database?.transaction(objectStore, 'readonly');

  if (tx === null || tx === undefined) {
    throw new Error(`Erro ao iniciar a transação readonly para "${objectStore}"`);
  }

  const store = tx.objectStore(objectStore);

  let data = null;
  if (index <= 0) {

    data = await store.getAll();

    if (params !== undefined && params.filter !== undefined) {
      if (params.filter.search !== undefined) {
        data = searchFilter(data, params.filter.search.toLowerCase());
      }
    }
  } else {
    data = await store.get(index);
  }

  return {
    data: {
      data: data
    }
  };
}

export async function retrieveDataPaginated(objectStore: string, params?: QueryParams) {
  if (database === null) {
    await createDB()
  }

  const tx = database?.transaction(objectStore, 'readonly');

  if (tx === null || tx === undefined) {
    throw new Error(`Erro ao iniciar a transação readonly para "${objectStore}"`);
  }

  const store = tx.objectStore(objectStore);

  if (params === undefined) {
    params = {};
  }

  const perPage = params.per_page ?? 10;

  let records: unknown[] = await store.getAll();

  if (params.filter?.search) {
    records = searchFilter(records, params.filter.search.toLowerCase());
  }

  if (params.cursor) {
    const decoded = JSON.parse(atob(params.cursor));
    records = records.filter((r: any) => r.id > decoded.id);
  }

  const data = records.slice(0, perPage);

  const config = useRuntimeConfig()
  const base = config.public.apiBase

  const next_cursor = data.length === perPage ? btoa(JSON.stringify({ id: (data[data.length - 1] as any).id, _pointsToNextItems: true })) : null;

  const nextUrl = next_cursor ? `${base}/${objectStore}?cursor=${next_cursor}` : null

  return {
    data: {
      data,
      links: {
        next: nextUrl,
        prev: null
      },
      meta: {
        next_cursor: next_cursor,
        per_page: perPage
      }
    }
  };
}

export async function retrievePaginatedByIncident(storeName: string, urlSegment: string, incidentID: number, params?: QueryParams) {
  if (database === null) {
    await createDB();
  }

  const tx = database?.transaction(storeName, 'readonly');

  if (tx === null || tx === undefined) {
    throw new Error(`Erro ao iniciar a transação readonly para ${storeName}`);
  }

  const store = tx.objectStore(storeName);

  if (params === undefined) {
    params = {};
  }

  const perPage = params.per_page ?? 10;

  let records: unknown[] = await store.index('incident_id').getAll(incidentID);

  if (params.filter?.search) {
    records = searchFilter(records, params.filter.search.toLowerCase());
  }

  if (params.cursor) {
    const decoded = JSON.parse(atob(params.cursor));
    records = records.filter((r: any) => r.id > decoded.id);
  }

  const data = records.slice(0, perPage);

  const config = useRuntimeConfig();
  const base = config.public.apiBase;

  const next_cursor =
    data.length === perPage
      ? btoa(JSON.stringify({ id: (data[data.length - 1] as any).id, _pointsToNextItems: true }))
      : null;

  const nextUrl = next_cursor
    ? `${base}/incidents/${incidentID}/${urlSegment}?cursor=${next_cursor}`
    : null;

  return {
    data: {
      data,
      links: {
        next: nextUrl,
        prev: null
      },
      meta: {
        next_cursor,
        per_page: perPage
      }
    }
  };
}

export async function removeEntry(objectStore: string, id: number) {
  if (database === null) {
    await createDB()
  }

  const tx = database?.transaction(objectStore, 'readwrite');

  if (tx === null || tx === undefined) {
    throw new Error(`Erro ao iniciar a transação readwrite para "${objectStore}"`);
  }

  const store = tx.objectStore(objectStore);

  await store.delete(id);
  await tx.done;
}

export async function clearTable(objectStore: string) {
  if (database === null) {
    await createDB();
  }

  await database?.clear(objectStore);
}

export async function clearSensitiveTables() {
  if (database === null) {
    await createDB();
  }

  await database?.clear('users');
  await database?.clear('volunteers');
}

function searchFilter(data: unknown[], searchString: string) {
  return data.filter((record) => {
    if (typeof record !== 'object' || record === null) return false;
    return Object.values(record).some((val) =>
      String(val).toLowerCase().includes(searchString)
    );
  })
}

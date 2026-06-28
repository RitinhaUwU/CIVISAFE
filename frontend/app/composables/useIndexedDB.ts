import {openDB, type IDBPDatabase} from "idb";
import type {QueryParams} from "~/types";

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

  // TODO: Incrementar o valor de da versão à medida que se fazem atualizações à estrutura

  database = await openDB('civisafe', 1, {
    upgrade(db, oldVersion, newVersion, transaction) {
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
        }

        case 1: {
          const facilitiesStore = db.createObjectStore('facilities', {keyPath: 'id'});
          facilitiesStore.createIndex('name', 'name', {unique: false});
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

    if(params !== undefined && params.filter !== undefined){
      if(params.filter.search !== undefined)
      {
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

  const next_cursor = data.length === perPage ? btoa(JSON.stringify({ id: (data[data.length - 1] as any).id, _pointsToNextItems: true })) : null;

  return {
    data: {
      data,
      links: {
        next: next_cursor ? `http://localhost?cursor=${next_cursor}` : null,
        prev: null
      },
      meta: {
        next_cursor: next_cursor,
        prev_cursor: null,
        per_page: perPage
      }
    }
  };
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

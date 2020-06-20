import createApiService from '@/services/ApiService'

describe('Testing fetchPackages', () => {
  it('Packages can be fetched', async () => {
    const fetchMock = jest.fn().mockReturnValue(Promise.resolve({
      ok: true,
      json: () => Promise.resolve({ count: 1, items: [] })
    }))

    expect(await createApiService(fetchMock).fetchPackages())
      .toEqual({ count: 1, items: [] })

    expect(fetchMock).toBeCalledWith(
      'http://localhost/api/packages',
      {
        credentials: 'omit',
        headers: { Accept: 'application/json' }
      }
    )
  })

  it('Fetching packages fails on server error', async () => {
    const fetchMock = jest.fn().mockReturnValue(Promise.resolve({
      ok: false,
      statusText: 'Server is down'
    }))

    expect.assertions(1)
    await createApiService(fetchMock).fetchPackages()
      .catch(error => { expect(error.toString()).toBeTruthy() })
  })
})

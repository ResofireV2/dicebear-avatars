import app from 'flarum/admin/app';
import * as avatarOptions from '@dicebear/collection';

app.initializers.add('resofire/dicebear', () => {
  const toKebabCase = (str: string) => {
    return str.replace(/([a-z])([A-Z])/g, '$1-$2').toLowerCase();
  };

  const options = Object.keys(avatarOptions).reduce((acc: { [key: string]: string }, key) => {
    const kebabKey = toKebabCase(key);
    acc[kebabKey] = app.translator.trans(`resofire-dicebear.admin.avatar_style_options.${kebabKey}`).toString();
    return acc;
  }, {});

  app.extensionData
    .for('resofire-dicebear')
    .registerSetting({
      setting: 'resofire-dicebear.avatar_style',
      label: app.translator.trans('resofire-dicebear.admin.avatar_style'),
      help: app.translator.trans('resofire-dicebear.admin.avatar_style_help', {
        a: <a href="https://www.dicebear.com/styles/" />,
      }),
      type: 'select',
      options,
    })
    .registerSetting({
      setting: 'resofire-dicebear.api_url',
      label: app.translator.trans('resofire-dicebear.admin.api_url'),
      help: app.translator.trans('resofire-dicebear.admin.api_url_help'),
      type: 'text',
    })
    .registerSetting(() => {
      let flushing = false;
      let statusMessage = '';

      const flush = async () => {
        if (flushing) return;
        flushing = true;
        statusMessage = '';

        try {
          const response = await fetch(`${app.forum.attribute('apiUrl')}/resofire-dicebear/flush`, {
            method: 'POST',
            headers: {
              'X-CSRF-Token': app.session.csrfToken,
              'Content-Type': 'application/json',
            },
          });

          const data = await response.json();
          statusMessage = app.translator.trans('resofire-dicebear.admin.flush_success', { count: data.flushed }).toString();
        } catch (e) {
          statusMessage = app.translator.trans('resofire-dicebear.admin.flush_error').toString();
        } finally {
          flushing = false;
          m.redraw();
        }
      };

      return (
        <div className="Form-group">
          <label>{app.translator.trans('resofire-dicebear.admin.flush_label')}</label>
          <p className="helpText">{app.translator.trans('resofire-dicebear.admin.flush_help')}</p>
          <button className="Button Button--danger" onclick={flush} disabled={flushing}>
            {flushing
              ? app.translator.trans('resofire-dicebear.admin.flush_running')
              : app.translator.trans('resofire-dicebear.admin.flush_button')}
          </button>
          {statusMessage ? <p style={{ marginTop: '8px' }}>{statusMessage}</p> : null}
        </div>
      );
    });
});
